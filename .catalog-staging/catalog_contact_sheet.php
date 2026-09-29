<?php

if ($argc < 4) {
    fwrite(STDERR, "Usage: php catalog_contact_sheet.php manifest.json group output.jpg\n");
    exit(1);
}

$json = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($argv[1]));
$manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
$group = $argv[2];
$items = array_values(array_filter($manifest['items'], static fn ($item) => $item['group'] === $group));
$columns = 4;
$cellWidth = 320;
$cellHeight = 245;
$rows = (int) ceil(count($items) / $columns);
$canvas = imagecreatetruecolor($columns * $cellWidth, max(1, $rows) * $cellHeight);
$white = imagecolorallocate($canvas, 255, 255, 255);
$black = imagecolorallocate($canvas, 25, 25, 25);
$gray = imagecolorallocate($canvas, 100, 100, 100);
$border = imagecolorallocate($canvas, 215, 215, 215);
imagefill($canvas, 0, 0, $white);

foreach ($items as $index => $item) {
    $column = $index % $columns;
    $row = intdiv($index, $columns);
    $x = $column * $cellWidth;
    $y = $row * $cellHeight;
    imagerectangle($canvas, $x, $y, $x + $cellWidth - 1, $y + $cellHeight - 1, $border);
    $sourceData = file_get_contents($item['source_image']);
    $source = imagecreatefromstring($sourceData);
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $maxWidth = $cellWidth - 20;
    $maxHeight = 175;
    $scale = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $width = max(1, (int) round($sourceWidth * $scale));
    $height = max(1, (int) round($sourceHeight * $scale));
    $imageX = $x + (int) (($cellWidth - $width) / 2);
    $imageY = $y + 8 + (int) (($maxHeight - $height) / 2);
    imagecopyresampled($canvas, $source, $imageX, $imageY, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
    imagedestroy($source);
    imagestring($canvas, 3, $x + 8, $y + 188, $item['catalogue_id'] . '  ' . $item['title'], $black);
    imagestring($canvas, 2, $x + 8, $y + 209, $item['colour'] . ' / ' . $item['supply_status'], $gray);
}

imagejpeg($canvas, $argv[3], 88);
imagedestroy($canvas);
