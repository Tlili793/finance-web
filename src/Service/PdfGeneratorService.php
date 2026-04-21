<?php
namespace App\Service;

/**
 * Generates a minimal insurance contract PDF using raw PDF 1.4 syntax.
 * No third-party library required.
 *
 * Usage:
 *   $path = $this->pdfGenerator->generateContractPdf(...);
 *   // … use $path …
 *   @unlink($path);   // always clean up
 */
class PdfGeneratorService
{
    /**
     * Builds the contract PDF and writes it to a temp file.
     *
     * @return string Absolute path to the generated PDF file
     */
    public function generateContractPdf(
        string             $userName,
        string             $assetReference,
        string             $insurancePackage,
        string             $approvedValue,
        \DateTimeInterface $contractDate,
        string             $terms,
    ): string {
        $dateStr = $contractDate->format('Y-m-d');
        $lines   = $this->buildLines($userName, $assetReference, $insurancePackage, $approvedValue, $dateStr, $terms);

        $pdf  = $this->buildPdf($lines);

        $path = tempnam(sys_get_temp_dir(), 'contract_') . '.pdf';
        file_put_contents($path, $pdf);

        return $path;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Builds the list of text lines to render in the PDF body. */
    private function buildLines(
        string $userName,
        string $assetReference,
        string $insurancePackage,
        string $approvedValue,
        string $dateStr,
        string $terms,
    ): array {
        return [
            ['text' => 'INSURANCE CONTRACT',         'size' => 18, 'bold' => true],
            ['text' => '',                            'size' => 12, 'bold' => false],
            ['text' => "Date: {$dateStr}",            'size' => 11, 'bold' => false],
            ['text' => '',                            'size' => 11, 'bold' => false],
            ['text' => 'Insured Party',               'size' => 13, 'bold' => true],
            ['text' => "Name:            {$userName}",'size' => 11, 'bold' => false],
            ['text' => '',                            'size' => 11, 'bold' => false],
            ['text' => 'Asset Details',               'size' => 13, 'bold' => true],
            ['text' => "Asset Reference: {$assetReference}", 'size' => 11, 'bold' => false],
            ['text' => '',                            'size' => 11, 'bold' => false],
            ['text' => 'Package Details',             'size' => 13, 'bold' => true],
            ['text' => "Package:         {$insurancePackage}", 'size' => 11, 'bold' => false],
            ['text' => "Premium:         {$approvedValue} TND", 'size' => 11, 'bold' => false],
            ['text' => '',                            'size' => 11, 'bold' => false],
            ['text' => 'Terms & Conditions',          'size' => 13, 'bold' => true],
            // word-wrap the terms to ~80 chars per line
            ...array_map(
                fn(string $line) => ['text' => $line, 'size' => 10, 'bold' => false],
                explode("\n", wordwrap($terms, 80, "\n", false))
            ),
            ['text' => '',                            'size' => 12, 'bold' => false],
            ['text' => '',                            'size' => 12, 'bold' => false],
            ['text' => '_______________________________', 'size' => 11, 'bold' => false],
            ['text' => 'Signature of insured party',  'size' => 10, 'bold' => false],
        ];
    }

    /**
     * Renders the line list into a minimal, valid PDF 1.4 byte string.
     *
     * Structure: one page (A4), Helvetica / Helvetica-Bold fonts only
     * (both are standard PDF base-14 fonts — no font embedding needed).
     */
    private function buildPdf(array $lines): string
    {
        // Page dimensions (A4 in points: 595 x 842)
        $pageW  = 595;
        $pageH  = 842;
        $margin = 50;
        $y      = $pageH - $margin;   // start near top

        // Build the BT…ET stream
        $stream = "BT\n";
        foreach ($lines as $line) {
            $font  = $line['bold'] ? 'Helvetica-Bold' : 'Helvetica';
            $size  = (int) $line['size'];
            $text  = $this->escapePdf($line['text']);
            $leading = $size + 4;

            $y -= $leading;
            if ($y < $margin) {
                // Simple overflow guard — truncate (BoldSign re-paginates anyway)
                break;
            }

            $stream .= "/{$font} {$size} Tf\n";
            $stream .= "{$margin} {$y} Td\n";
            $stream .= "({$text}) Tj\n";
            $stream .= "0 0 Td\n";   // reset translation so next Td is absolute
        }
        $stream .= "ET\n";

        // ── Objects ──────────────────────────────────────────────────────────
        $objects = [];
        $offsets = [];

        // 1: Catalog
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        // 2: Pages
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";

        // 3: Page
        $objects[3] = implode("\n", [
            "<< /Type /Page",
            "   /Parent 2 0 R",
            "   /MediaBox [0 0 {$pageW} {$pageH}]",
            "   /Contents 4 0 R",
            "   /Resources <<",
            "     /Font <<",
            "       /Helvetica      << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "       /Helvetica-Bold << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>",
            "     >>",
            "   >>",
            ">>",
        ]);

        // 4: Content stream
        $streamLen   = strlen($stream);
        $objects[4]  = "<< /Length {$streamLen} >>\nstream\n{$stream}endstream";

        // ── Write body ───────────────────────────────────────────────────────
        $body    = "%PDF-1.4\n";
        $body   .= "%\xE2\xE3\xCF\xD3\n";   // binary comment (marks as binary file)

        foreach ($objects as $id => $obj) {
            $offsets[$id] = strlen($body);
            $body        .= "{$id} 0 obj\n{$obj}\nendobj\n";
        }

        // ── xref table ───────────────────────────────────────────────────────
        $xrefOffset = strlen($body);
        $count      = count($objects) + 1;   // +1 for the free entry

        $xref  = "xref\n";
        $xref .= "0 {$count}\n";
        $xref .= "0000000000 65535 f \n";   // free entry

        for ($i = 1; $i < $count; $i++) {
            $xref .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        // ── trailer ──────────────────────────────────────────────────────────
        $trailer  = "trailer\n";
        $trailer .= "<< /Size {$count} /Root 1 0 R >>\n";
        $trailer .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $body . $xref . $trailer;
    }

    /** Escape special characters for PDF string literals. */
    private function escapePdf(string $text): string
    {
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', '', ''],
            $text
        );
    }
}
