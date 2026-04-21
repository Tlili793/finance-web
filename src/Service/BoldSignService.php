<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class BoldSignService
{
    public function __construct(
        private HttpClientInterface  $httpClient,
        private PdfGeneratorService  $pdfGenerator,
        private string $apiKey,
        private string $baseUrl,
        private string $webhookUrl,
    ) {}

    /**
     * Generates the contract PDF and sends it to the signer via BoldSign.
     * Returns the document ID from the BoldSign API.
     */
    public function sendForSignature(
        string             $userName,
        string             $assetReference,
        string             $insurancePackage,
        string             $approvedValue,
        \DateTimeInterface $contractDate,
        string             $signerEmail,
    ): string {
        // ── Step 1: generate PDF ─────────────────────────────────────
        $terms = sprintf(
            'This insurance contract covers the insured asset against all declared risks. '
            . 'The insured party (%s) agrees to the terms set out by the %s package. '
            . 'Any fraudulent claim will void this contract.',
            $userName,
            $insurancePackage
        );

        $pdfPath = $this->pdfGenerator->generateContractPdf(
            $userName,
            $assetReference,
            $insurancePackage,
            $approvedValue,
            $contractDate,
            $terms,
        );

        // ── Step 2: send to BoldSign ─────────────────────────────────
        try {
            $documentId = $this->sendToApi(
                $pdfPath,
                $userName,
                $assetReference,
                $signerEmail,
            );
        } finally {
            @unlink($pdfPath);  // always clean up the temp PDF
        }

        return $documentId;
    }

    // ─────────────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────────────

    private function sendToApi(
        string $pdfPath,
        string $userName,
        string $assetReference,
        string $signerEmail,
    ): string {
        $formFields = [
            'Title' => 'Insurance Contract - ' . $assetReference,
            'Signers[0].Name' => $userName,
            'Signers[0].EmailAddress' => $signerEmail,
            'Signers[0].SignerOrder' => '1',
            'Signers[0].FormFields[0].FieldType' => 'Signature',
            'Signers[0].FormFields[0].PageNumber' => '1',
            'Signers[0].FormFields[0].Bounds.X' => '100',
            'Signers[0].FormFields[0].Bounds.Y' => '600',
            'Signers[0].FormFields[0].Bounds.Width' => '200',
            'Signers[0].FormFields[0].Bounds.Height' => '50',
            'Signers[0].FormFields[0].IsRequired' => 'true',
            'WebhookUrl' => $this->webhookUrl,
            'Files' => DataPart::fromPath($pdfPath, 'contract.pdf', 'application/pdf')
        ];

        $formData = new FormDataPart($formFields);

        $response = $this->httpClient->request('POST', $this->baseUrl . '/v1/document/send', [
            'headers' => array_merge(
                ['X-API-KEY' => $this->apiKey],
                $formData->getPreparedHeaders()->toArray()
            ),
            'body' => $formData->bodyToIterable(),
        ]);

        $statusCode = $response->getStatusCode();
        $body       = $response->getContent(false);  // false = don't throw on 4xx/5xx

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(
                sprintf('BoldSign API error %d: %s', $statusCode, $body)
            );
        }

        $data = json_decode($body, true);
        return $data['documentId'] ?? $body;
    }
}
