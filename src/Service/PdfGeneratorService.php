<?php
namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

class PdfGeneratorService
{
    public function __construct(
        private Environment $twig,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {}

    /**
     * Renders the contract template and converts it to a PDF using Dompdf.
     * Returns the absolute path to the generated PDF file.
     */
    public function generateContractPdf(
        int    $userId,
        int    $requestId,
        string $userName,
        string $assetReference,
        string $insurancePackage,
        string $approvedValue,
        \DateTimeInterface $contractDate,
        string $terms,
    ): string {
        // ── Render HTML ──────────────────────────────────────────────
        $html = $this->twig->render('contracts/contract.html.twig', [
            'userName'         => $userName,
            'assetReference'   => $assetReference,
            'insurancePackage' => $insurancePackage,
            'approvedValue'    => $approvedValue,
            'contractDate'     => $contractDate->format('Y-m-d'),
            'terms'            => $terms,
            'requestId'        => $requestId,
        ]);

        // ── Directory Structure: var/contracts/requests/{userId}/ ──
        $dir = $this->projectDir . "/var/contracts/requests/{$userId}";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdfPath = $dir . "/contract_{$requestId}.pdf";

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($pdfPath, $dompdf->output());

        if (!file_exists($pdfPath)) {
            throw new \RuntimeException('PDF generation failed to write output file.');
        }

        return $pdfPath;
    }
}