<?php
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/app'));
$results = [];

foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $relPath = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relPath = str_replace('\\', '/', $relPath);
        
        $matches = [];
        preg_match_all('/\b(hotel_id|hotelMemberships)\b/i', $content, $matches);
        if (!empty($matches[0])) {
            $results[$relPath] = count($matches[0]);
        }
    }
}

ksort($results);
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
