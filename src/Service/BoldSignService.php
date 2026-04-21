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
     * Generates the contract PDF, saves it to var/contracts/requests/{userId}/,
     * then sends it to the signer via BoldSign.
     *
     * Returns the BoldSign document ID.
     */
    public function sendForSignature(
        string             $userName,
        string             $userEmail,
        string             $assetReference,
        string             $assetType,
        string             $insurancePackage,
        string             $coverageDetails,
        string             $approvedValue,
        \DateTimeInterface $contractDate,
        string             $signerEmail,
        int                $userId,
        int                $requestId,
    ): string {
        $terms = sprintf(
            'This insurance contract is entered into between FinanceApp Insurance Services '
            . '(hereinafter "the Insurer") and %s (hereinafter "the Insured"). '
            . 'The Insured asset (%s) is covered under the %s package for all declared risks '
            . 'for a period of twelve (12) months from the contract date. '
            . 'The agreed annual premium of %s TND is due upon signing. '
            . 'Coverage is void in case of fraud, wilful misconduct, or breach of contract terms. '
            . 'Disputes shall be resolved under applicable Tunisian insurance law.',
            $userName, $assetReference, $insurancePackage, $approvedValue
        );

        // ── Step 1: Generate & persist the PDF ───────────────────────────────
        $pdfPath = $this->pdfGenerator->generateContractPdf(
            userName:         $userName,
            userEmail:        $userEmail,
            assetReference:   $assetReference,
            assetType:        $assetType,
            insurancePackage: $insurancePackage,
            coverageDetails:  $coverageDetails,
            approvedValue:    $approvedValue,
            contractDate:     $contractDate,
            terms:            $terms,
            userId:           $userId,
            requestId:        $requestId,
        );

        // ── Step 2: Send to BoldSign (keep the file — do NOT delete it) ───────
        return $this->sendToApi(
            pdfPath:        $pdfPath,
            userName:       $userName,
            assetReference: $assetReference,
            signerEmail:    $signerEmail,
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function sendToApi(
        string $pdfPath,
        string $userName,
        string $assetReference,
        string $signerEmail,
    ): string {
        $formFields = [
            'Title'                                        => 'Insurance Contract - ' . $assetReference,
            'Signers[0].Name'                             => $userName,
            'Signers[0].EmailAddress'                     => $signerEmail,
            'Signers[0].SignerOrder'                      => '1',
            'Signers[0].FormFields[0].FieldType'          => 'Signature',
            'Signers[0].FormFields[0].PageNumber'         => '1',
            // Positioned over the [Sign Here] box drawn in the PDF (points from bottom-left)
            'Signers[0].FormFields[0].Bounds.X'           => '50',
            'Signers[0].FormFields[0].Bounds.Y'           => '780',
            'Signers[0].FormFields[0].Bounds.Width'       => '200',
            'Signers[0].FormFields[0].Bounds.Height'      => '40',
            'Signers[0].FormFields[0].IsRequired'         => 'true',
            'WebhookUrl'                                  => $this->webhookUrl,
            'Files'                                       => DataPart::fromPath($pdfPath, 'contract.pdf', 'application/pdf'),
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
        $body       = $response->getContent(false);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(
                sprintf('BoldSign API error %d: %s', $statusCode, $body)
            );
        }

        $data = json_decode($body, true);
        return $data['documentId'] ?? $body;
    }
}
