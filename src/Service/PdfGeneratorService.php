<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Generates a professional insurance contract PDF using raw PDF 1.4 syntax.
 * No third-party library required.
 *
 * The PDF is saved to:  <projectDir>/var/contracts/requests/<userId>/<filename>
 *
 * Usage:
 *   $path = $generator->generateContractPdf(..., userId: 42, requestId: 7);
 *   // path is persisted — caller must clean it up when done
 */
class PdfGeneratorService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {}

    /**
     * Builds the contract PDF and writes it to:
     *   var/contracts/requests/{userId}/contract_{requestId}.pdf
     *
     * @return string Absolute path to the generated PDF file
     */
    public function generateContractPdf(
        string             $userName,
        string             $userEmail,
        string             $assetReference,
        string             $assetType,
        string             $insurancePackage,
        string             $coverageDetails,
        string             $approvedValue,
        \DateTimeInterface $contractDate,
        string             $terms,
        int                $userId,
        int                $requestId,
    ): string {
        $dir = $this->projectDir . '/var/contracts/requests/' . $userId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "contract_{$requestId}.pdf";
        $path     = $dir . '/' . $filename;

        $pdf = $this->buildPdf(
            $userName, $userEmail, $assetReference, $assetType,
            $insurancePackage, $coverageDetails, $approvedValue,
            $contractDate->format('d / m / Y'), $terms
        );

        file_put_contents($path, $pdf);
        return $path;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PDF builder — raw PDF 1.4
    // ─────────────────────────────────────────────────────────────────────────

    private function buildPdf(
        string $userName,
        string $userEmail,
        string $assetReference,
        string $assetType,
        string $packageName,
        string $coverageDetails,
        string $premium,
        string $dateStr,
        string $terms,
    ): string {
        $W = 595;   // A4 width  in points
        $H = 842;   // A4 height in points
        $m = 50;    // left margin

        // ── Build page content stream ─────────────────────────────────────────
        $s = '';    // stream accumulator

        // Helper closures
        $rgb  = fn(float $r, float $g, float $b) => sprintf("%.3f %.3f %.3f", $r, $g, $b);
        $rect = fn(float $x, float $y, float $w, float $h) => "{$x} {$y} {$w} {$h} re\n";
        $text = fn(string $t, float $x, float $y, string $font, int $size, array $color = [0, 0, 0]) =>
            "BT\n/{$font} {$size} Tf\n" .
            "{$rgb($color[0], $color[1], $color[2])} rg\n" .
            "{$x} {$y} Td\n" .
            "(" . $this->escapePdf($t) . ") Tj\n" .
            "ET\n";

        // ── Header bar ────────────────────────────────────────────────────────
        // Dark blue rectangle across full width
        $s .= "{$rgb(0.063, 0.224, 0.459)} rg\n";
        $s .= $rect(0, $H - 75, $W, 75);
        $s .= "f\n";

        // Company / product name in white
        $s .= $text('FinanceApp Insurance', $m, $H - 48, 'Helvetica-Bold', 20, [1, 1, 1]);
        $s .= $text('Official Contract of Insurance', $m, $H - 66, 'Helvetica', 10, [0.78, 0.87, 1.0]);

        // Contract date (top-right)
        $s .= $text("Date: {$dateStr}", $W - 165, $H - 50, 'Helvetica', 9, [1, 1, 1]);

        // ── Light accent band ─────────────────────────────────────────────────
        $s .= "{$rgb(0.94, 0.97, 1.0)} rg\n";
        $s .= $rect(0, $H - 110, $W, 35);
        $s .= "f\n";
        $s .= $text("CONTRACT REF: INS-{$this->escapePdf($assetReference)}-" . date('Y'), $m, $H - 98, 'Helvetica-Bold', 9, [0.063, 0.224, 0.459]);

        // ── Reset to black for body ───────────────────────────────────────────
        $s .= "0 0 0 rg\n";

        // ── Section: Insured Party ────────────────────────────────────────────
        $y = $H - 140;
        $s .= $this->sectionHeader('Insured Party', $m, $y, $W, $rgb);
        $y -= 22;
        $s .= $this->twoColRow('Full Name',    $userName,   $m, $y, $rgb); $y -= 18;
        $s .= $this->twoColRow('Email',        $userEmail,  $m, $y, $rgb); $y -= 24;

        // ── Section: Asset Details ────────────────────────────────────────────
        $s .= $this->sectionHeader('Asset Details', $m, $y, $W, $rgb);
        $y -= 22;
        $s .= $this->twoColRow('Asset Reference', $assetReference, $m, $y, $rgb); $y -= 18;
        $s .= $this->twoColRow('Asset Type',      $assetType,      $m, $y, $rgb); $y -= 24;

        // ── Section: Package & Premium ────────────────────────────────────────
        $s .= $this->sectionHeader('Package & Premium', $m, $y, $W, $rgb);
        $y -= 22;
        $s .= $this->twoColRow('Package Name',     $packageName,    $m, $y, $rgb); $y -= 18;
        $s .= $this->twoColRow('Coverage Details', $coverageDetails, $m, $y, $rgb); $y -= 18;

        // Highlighted premium box
        $y -= 4;
        $s .= "{$rgb(0.063, 0.224, 0.459)} rg\n";
        $s .= $rect($m, $y - 4, $W - ($m * 2), 24);
        $s .= "f\n";
        $s .= $text("Annual Premium:  {$premium} TND", $m + 8, $y + 5, 'Helvetica-Bold', 11, [1, 1, 1]);
        $y -= 32;

        // ── Section: Terms & Conditions ───────────────────────────────────────
        $s .= $this->sectionHeader('Terms & Conditions', $m, $y, $W, $rgb);
        $y -= 20;
        $s .= "0 0 0 rg\n";
        foreach (explode("\n", wordwrap($terms, 90, "\n", false)) as $line) {
            if ($y < 90) break;
            $s .= $text($line, $m, $y, 'Helvetica', 9, [0.2, 0.2, 0.2]);
            $y -= 13;
        }

        // ── Signature area ────────────────────────────────────────────────────
        $y = 120;
        // Horizontal rule above signature
        $s .= "{$rgb(0.063, 0.224, 0.459)} rg\n";
        $s .= $rect($m, $y + 48, $W - ($m * 2), 1);
        $s .= "f\n";
        $s .= "0 0 0 rg\n";
        $s .= $text('Signature of the Insured Party', $m, $y + 34, 'Helvetica', 9, [0.4, 0.4, 0.4]);

        // Signature box outline (BoldSign places the widget here)
        $s .= "0.063 0.224 0.459 RG\n";   // stroke color
        $s .= "0.5 w\n";                   // line width
        $s .= $rect($m, $y - 10, 200, 40);
        $s .= "S\n";
        $s .= $text('[Sign Here]', $m + 60, $y + 8, 'Helvetica', 9, [0.5, 0.5, 0.5]);

        $s .= $text($userName, $m, $y - 24, 'Helvetica', 9, [0.2, 0.2, 0.2]);
        $s .= $text($dateStr, $m + 220, $y - 24, 'Helvetica', 9, [0.2, 0.2, 0.2]);

        // ── Footer band ───────────────────────────────────────────────────────
        $s .= "{$rgb(0.063, 0.224, 0.459)} rg\n";
        $s .= $rect(0, 0, $W, 28);
        $s .= "f\n";
        $s .= $text('FinanceApp Insurance Services  |  www.financeapp.tn  |  contact@financeapp.tn', $m, 10, 'Helvetica', 7, [0.78, 0.87, 1.0]);

        // ── Assemble PDF structure ────────────────────────────────────────────
        return $this->assemblePdf($s, $W, $H);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Layout helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function sectionHeader(string $title, float $x, float $y, float $pageW, callable $rgb): string
    {
        $out  = "{$rgb(0.22, 0.42, 0.70)} rg\n";
        $out .= "{$x} {$y} " . ($pageW - $x * 2) . " 16 re\n";
        $out .= "f\n";
        $out .= "BT\n/Helvetica-Bold 9 Tf\n1 1 1 rg\n{$x} " . ($y + 4) . " Td\n";
        $out .= "(" . $this->escapePdf(strtoupper($title)) . ") Tj\nET\n";
        return $out;
    }

    private function twoColRow(string $label, string $value, float $x, float $y, callable $rgb): string
    {
        $labelCol = $x;
        $valueCol = $x + 150;
        $out  = "BT\n/Helvetica-Bold 9 Tf\n{$rgb(0.3, 0.3, 0.3)} rg\n{$labelCol} {$y} Td\n";
        $out .= "(" . $this->escapePdf($label) . ") Tj\nET\n";
        $out .= "BT\n/Helvetica 9 Tf\n0 0 0 rg\n{$valueCol} {$y} Td\n";
        $out .= "(" . $this->escapePdf($value) . ") Tj\nET\n";
        return $out;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PDF byte assembly
    // ─────────────────────────────────────────────────────────────────────────

    private function assemblePdf(string $stream, int $W, int $H): string
    {
        $objects = [];
        $offsets = [];

        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = implode("\n", [
            "<< /Type /Page",
            "   /Parent 2 0 R",
            "   /MediaBox [0 0 {$W} {$H}]",
            "   /Contents 4 0 R",
            "   /Resources <<",
            "     /Font <<",
            "       /Helvetica      << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "       /Helvetica-Bold << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>",
            "     >>",
            "   >>",
            ">>",
        ]);
        $len = strlen($stream);
        $objects[4] = "<< /Length {$len} >>\nstream\n{$stream}endstream";

        $body  = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        foreach ($objects as $id => $obj) {
            $offsets[$id] = strlen($body);
            $body        .= "{$id} 0 obj\n{$obj}\nendobj\n";
        }

        $xrefOff = strlen($body);
        $count   = count($objects) + 1;
        $xref    = "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $xref .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        return $body . $xref .
            "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOff}\n%%EOF\n";
    }

    private function escapePdf(string $text): string
    {
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', '', ' '],
            $text
        );
    }
}
