<?php
require 'vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

try {
    $qrCode = new QrCode('Test');
    $writer = new SvgWriter();
    $result = $writer->write($qrCode);
    echo "SVG length: " . strlen($result->getString()) . "\n";
    echo "Success\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
