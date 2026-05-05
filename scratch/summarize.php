<?php
$data = json_decode(file_get_contents(__DIR__.'/../phpstan-report.json'), true);
$files = [];
foreach ($data['files'] as $file => $info) {
    $files[$file] = count($info['messages']);
}
arsort($files);
foreach (array_slice($files, 0, 10) as $file => $count) {
    echo "$count errors in $file\n";
}
