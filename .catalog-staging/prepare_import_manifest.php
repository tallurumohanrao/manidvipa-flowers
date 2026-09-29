<?php

if ($argc < 4) {
    fwrite(STDERR, "Usage: php prepare_import_manifest.php catalog.json matches.json master-directory\n");
    exit(1);
}

function readJson(string $path): array
{
    $contents = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($path));
    return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
}

$catalog = readJson($argv[1]);
$matches = readJson($argv[2]);
$masterDirectory = realpath($argv[3]);
$matchesByCorporate = [];
foreach ($matches as $match) {
    $matchesByCorporate[$match['corporate']] = $match;
}

$existingProducts = [
    'MF-121' => 54,
    'MF-122' => 34,
    'MF-123' => 2,
    'MF-124' => 32,
    'MF-135' => 39,
    'MF-137' => 66,
    'MF-138' => 36,
    'MF-139' => 67,
    'MF-140' => 42,
    'MF-142' => 43,
    'MF-143' => 48,
    'MF-146' => 56,
    'MF-149' => 55,
];

foreach ($catalog as &$item) {
    $match = $matchesByCorporate[$item['pdf_image']] ?? null;
    if (!$match || !$match['best']) {
        throw new RuntimeException('No master image match for ' . $item['catalogue_id']);
    }
    $item['source_image'] = $masterDirectory . DIRECTORY_SEPARATOR . $match['best']['master'];
    $item['image_match_score'] = round((float) $match['best']['score'], 4);
    $item['existing_product_id'] = $existingProducts[$item['catalogue_id']] ?? null;
}
unset($item);

echo json_encode([
    'catalogue' => 'Manidvipa Corporate Flower Catalogue 2026',
    'source_count' => count($catalog),
    'items' => $catalog,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL;
