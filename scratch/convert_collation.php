<?php

require 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load('.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$conn = $em->getConnection();

$tables = $conn->iterateAssociative("SHOW TABLES");

echo "Converting tables to utf8mb4_unicode_ci...\n";

foreach ($tables as $row) {
    $tableName = array_values($row)[0];
    echo "Processing $tableName...\n";
    $conn->executeStatement("ALTER TABLE `$tableName` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

echo "Done!\n";
