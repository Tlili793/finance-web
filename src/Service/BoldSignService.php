<?php
namespace App\Service;      // ← note: Service not Services

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BoldSignService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private PdfGeneratorService $pdfGenerator,
        private string $apiKey,
        private string $baseUrl,
        private string $webhookUrl,
    ) {}

    public function sendForSignature(
        mixed              $userId,
        mixed              $requestId,
        string             $userName,
        string             $assetReference,
        string             $insurancePackage,
        string             $approvedValue,
        \DateTimeInterface $contractDate,
        string             $signerEmail,
    ): string {
        $terms = sprintf(
            'This insurance contract covers the insured asset against all declared risks. '
            . 'The insured party (%s) agrees to the terms set out by the %s package. '
            . 'Any fraudulent claim will void this contract.',
            $userName,
            $insurancePackage
        );

        $pdfPath = $this->pdfGenerator->generateContractPdf(
            $userId,
            $requestId,
            $userName,
            $assetReference,
            $insurancePackage,
            $approvedValue,
            $contractDate,
            $terms,
        );

        // Note: We no longer unlink($pdfPath) because the user wants to keep it in requests/{userId}
        return $this->sendToApi($pdfPath, $userName, $assetReference, $signerEmail);
    }

    private function sendToApi(
        string $pdfPath,
        string $userName,
        string $assetReference,
        string $signerEmail,
    ): string {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/v1/document/send', [
            'headers' => ['X-API-KEY' => $this->apiKey],
            'body'    => $this->buildMultipartBody($pdfPath, $userName, $assetReference, $signerEmail),
        ]);

        $statusCode = $response->getStatusCode();
        $body       = $response->getContent(false);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(
                sprintf('BoldSign API error %d: %s', $statusCode, $body)
            );
        }

        $data = json_decode($body, true);
        return $data['documentId'] ?? $body;
    }

    private function buildMultipartBody(
        string $pdfPath,
        string $userName,
        string $assetReference,
        string $signerEmail,
    ): array {
        return [
            'Files'                                     => fopen($pdfPath, 'r'),
            'Title'                                     => 'Insurance Contract - ' . $assetReference,
            'Signers[0][Name]'                          => $userName,
            'Signers[0][EmailAddress]'                  => $signerEmail,
            'Signers[0][SignerOrder]'                   => '1',
            'Signers[0][FormFields][0][FieldType]'      => 'Signature',
            'Signers[0][FormFields][0][PageNumber]'     => '1',
            'Signers[0][FormFields][0][Bounds][X]'      => '100',
            'Signers[0][FormFields][0][Bounds][Y]'      => '600',
            'Signers[0][FormFields][0][Bounds][Width]'  => '200',
            'Signers[0][FormFields][0][Bounds][Height]' => '50',
            'Signers[0][FormFields][0][IsRequired]'     => 'true',
            'WebhookUrl'                                => $this->webhookUrl,
        ];
    }
}