<?php

$textPath = $argv[1] ?? null;
$pagesPath = $argv[2] ?? null;
if (!$textPath || !$pagesPath) {
    fwrite(STDERR, "Usage: php build_catalog_manifest.php catalogue.txt pages.json\n");
    exit(1);
}

$lines = preg_split('/\R/', trim(file_get_contents($textPath)));
$pagesJson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($pagesPath));
$pages = json_decode($pagesJson, true, 512, JSON_THROW_ON_ERROR);

$groups = [
    [1, 6, 'Alstroemeria'],
    [7, 11, 'Anthurium'],
    [12, 18, 'Carnations'],
    [19, 35, 'Chrysanthemums'],
    [36, 40, 'Dried / Preserved'],
    [41, 52, 'Fillers'],
    [53, 64, 'Foliage'],
    [65, 71, 'Gerbera'],
    [72, 77, 'Gladiolus'],
    [78, 91, 'Lilies'],
    [92, 104, 'Orchids'],
    [105, 120, 'Premium Seasonal'],
    [121, 134, 'Roses'],
    [135, 149, 'Traditional / Pooja'],
    [150, 157, 'Tropical & Exotic'],
    [158, 166, 'Tulips'],
];

$imageObjects = [];
foreach (array_slice($pages, 3, 24) as $page) {
    foreach ($page['draws'] as $draw) {
        $imageObjects[] = $draw['object'];
    }
}

$entries = [];
for ($index = 0; $index < count($lines); $index++) {
    if (!preg_match('/^MF-(\d{3})$/', trim($lines[$index]), $idMatch)) {
        continue;
    }
    $number = (int) $idMatch[1];
    $buffer = [];
    for ($next = $index + 1; $next < count($lines); $next++) {
        $candidate = trim($lines[$next]);
        if (preg_match('/^MF-\d{3}$/', $candidate) || str_starts_with($candidate, 'MANIDVIPA FLOWERS') || str_starts_with($candidate, 'COLLECTION ')) {
            break;
        }
        if ($candidate !== '') {
            $buffer[] = $candidate;
        }
    }

    $colourAt = null;
    foreach ($buffer as $offset => $value) {
        if (str_starts_with($value, 'COLOUR:')) {
            $colourAt = $offset;
            break;
        }
    }
    if ($colourAt === null) {
        continue;
    }
    $title = trim(implode(' ', array_slice($buffer, 0, $colourAt)));
    $colourParts = [trim(substr($buffer[$colourAt], strlen('COLOUR:')))];
    $status = null;
    $statusAt = null;
    for ($offset = $colourAt + 1; $offset < count($buffer); $offset++) {
        if (in_array($buffer[$offset], ['CORE', 'SEASONAL', 'PRE-ORDER', 'IMPORTED/SEASONAL'], true)) {
            $status = $buffer[$offset];
            $statusAt = $offset;
            break;
        }
        $colourParts[] = $buffer[$offset];
    }
    $uses = $statusAt === null ? '' : trim(implode(' ', array_slice($buffer, $statusAt + 1)));
    $group = null;
    foreach ($groups as [$first, $last, $name]) {
        if ($number >= $first && $number <= $last) {
            $group = $name;
            break;
        }
    }
    $object = $imageObjects[$number - 1] ?? null;
    $entries[] = [
        'catalogue_id' => sprintf('MF-%03d', $number),
        'number' => $number,
        'group' => $group,
        'title' => preg_replace('/\s+/', ' ', $title),
        'colour' => preg_replace('/\s+/', ' ', trim(implode(' ', $colourParts))),
        'supply_status' => $status,
        'recommended_uses' => $uses,
        'pdf_image_object' => $object,
        'pdf_image' => $object ? sprintf('object-%04d.jpg', $object) : null,
    ];
}

usort($entries, static fn ($a, $b) => $a['number'] <=> $b['number']);
echo json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL;
