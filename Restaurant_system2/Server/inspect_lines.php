<?php
$file = $argv[1] ?? '';
$search = $argv[2] ?? 'hotel_id';
if (!$file || !file_exists($file)) {
    die("File not found\n");
}
$lines = file($file);
foreach ($lines as $i => $line) {
    if (stripos($line, $search) !== false) {
        echo ($i + 1) . ': ' . trim($line) . "\n";
    }
}
