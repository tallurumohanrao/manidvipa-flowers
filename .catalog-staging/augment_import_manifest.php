<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php augment_import_manifest.php manifest.json latest-photos-directory\n");
    exit(1);
}

$json = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($argv[1]));
$manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
$photos = realpath($argv[2]);
$photo = static fn (string $name): string => $photos . DIRECTORY_SEPARATOR . $name;

$additionalImages = [
    'MF-059' => [$photo('WhatsApp Image 2026-09-28 at 2.49.02 PM (1).jpeg')],
    'MF-062' => [$photo('WhatsApp Image 2026-09-28 at 2.49.00 PM.jpeg')],
    'MF-096' => [$photo('WhatsApp Image 2026-09-25 at 8.38.16 PM.jpeg')],
    'MF-150' => [$photo('WhatsApp Image 2026-09-28 at 2.49.02 PM (2).jpeg')],
    'MF-151' => [$photo('WhatsApp Image 2026-09-28 at 2.49.03 PM (1).jpeg')],
];

foreach ($manifest['items'] as &$item) {
    $item['additional_images'] = $additionalImages[$item['catalogue_id']] ?? [];
    if ($item['catalogue_id'] === 'MF-062') {
        $item['source_image'] = $photo('WhatsApp Image 2026-09-28 at 2.49.00 PM.jpeg');
        $item['additional_images'] = [];
    }
}
unset($item);

$manifest['items'][] = [
    'catalogue_id' => 'MF-X01',
    'number' => 1001,
    'group' => 'Fillers',
    'title' => 'Hypericum Berries',
    'colour' => 'PEACH',
    'supply_status' => 'IMPORTED/SEASONAL',
    'recommended_uses' => 'Bouquets, premium arrangements and event floristry',
    'source_image' => $photo('WhatsApp Image 2026-09-25 at 8.38.17 PM.jpeg'),
    'additional_images' => [],
    'existing_product_id' => null,
];
$manifest['items'][] = [
    'catalogue_id' => 'MF-X02',
    'number' => 1002,
    'group' => 'Foliage',
    'title' => 'Fishtail Palm Foliage',
    'colour' => 'GREEN',
    'supply_status' => 'CORE',
    'recommended_uses' => 'Bouquets, stage decor and large floral arrangements',
    'source_image' => $photo('WhatsApp Image 2026-09-28 at 2.49.01 PM.jpeg'),
    'additional_images' => [],
    'existing_product_id' => null,
];
$manifest['items'][] = [
    'catalogue_id' => 'MF-X03',
    'number' => 1003,
    'group' => 'Fillers',
    'title' => 'Hanging Amaranthus',
    'colour' => 'BURGUNDY',
    'supply_status' => 'SEASONAL',
    'recommended_uses' => 'Hanging installations, premium events and statement arrangements',
    'source_image' => $photo('WhatsApp Image 2026-09-28 at 2.49.04 PM.jpeg'),
    'additional_images' => [$photo('WhatsApp Image 2026-09-28 at 2.49.03 PM.jpeg')],
    'existing_product_id' => null,
];
$manifest['items'][] = [
    'catalogue_id' => 'MF-X04',
    'number' => 1004,
    'group' => 'Premium Seasonal',
    'title' => 'Hydrangea',
    'colour' => 'PALE PINK',
    'supply_status' => 'IMPORTED/SEASONAL',
    'recommended_uses' => 'Premium bouquets, weddings and hospitality arrangements',
    'source_image' => $photo('WhatsApp Image 2026-09-28 at 2.49.02 PM.jpeg'),
    'additional_images' => [],
    'existing_product_id' => null,
];

$manifest['category_images'] = [
    'Anthurium' => $photo('WhatsApp Image 2026-09-28 at 2.49.01 PM (1).jpeg'),
    'Foliage' => $photo('WhatsApp Image 2026-09-28 at 2.49.01 PM.jpeg'),
];
$manifest['extra_product_images'] = [
    ['product_id' => 63, 'source_image' => $photo('WhatsApp Image 2026-09-28 at 2.49.01 PM (2).jpeg'), 'label' => 'fresh-lily-buds'],
];
$manifest['source_count'] = count($manifest['items']);

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL;
