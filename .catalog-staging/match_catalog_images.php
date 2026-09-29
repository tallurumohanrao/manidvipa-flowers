<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php match_catalog_images.php corporate-directory master-directory\n");
    exit(1);
}

function loadImage(string $file): GdImage|false
{
    $data = @file_get_contents($file);
    $image = $data === false ? false : @imagecreatefromstring($data);
    if (!$image) {
        return false;
    }
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($file);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };
        if ($rotated instanceof GdImage) {
            imagedestroy($image);
            $image = $rotated;
        }
    }
    return $image;
}

function signature(string $file, bool $crop): ?array
{
    $source = loadImage($file);
    if (!$source) {
        return null;
    }
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $targetWidth = 24;
    $targetHeight = 16;
    $sourceX = 0;
    $sourceY = 0;
    $copyWidth = $sourceWidth;
    $copyHeight = $sourceHeight;
    if ($crop) {
        $targetRatio = $targetWidth / $targetHeight;
        $sourceRatio = $sourceWidth / $sourceHeight;
        if ($sourceRatio > $targetRatio) {
            $copyWidth = (int) round($sourceHeight * $targetRatio);
            $sourceX = (int) floor(($sourceWidth - $copyWidth) / 2);
        } elseif ($sourceRatio < $targetRatio) {
            $copyHeight = (int) round($sourceWidth / $targetRatio);
            $sourceY = (int) floor(($sourceHeight - $copyHeight) / 2);
        }
    }
    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    imagecopyresampled($target, $source, 0, 0, $sourceX, $sourceY, $targetWidth, $targetHeight, $copyWidth, $copyHeight);
    $values = [];
    for ($y = 0; $y < $targetHeight; $y++) {
        for ($x = 0; $x < $targetWidth; $x++) {
            $rgb = imagecolorat($target, $x, $y);
            $values[] = ($rgb >> 16) & 255;
            $values[] = ($rgb >> 8) & 255;
            $values[] = $rgb & 255;
        }
    }
    imagedestroy($target);
    imagedestroy($source);
    return $values;
}

function distance(array $first, array $second): float
{
    $sum = 0;
    $count = min(count($first), count($second));
    for ($index = 0; $index < $count; $index++) {
        $sum += abs($first[$index] - $second[$index]);
    }
    return $count ? $sum / $count : INF;
}

$corporateFiles = array_merge(
    glob(rtrim($argv[1], '/\\') . DIRECTORY_SEPARATOR . '*.jpg'),
    glob(rtrim($argv[1], '/\\') . DIRECTORY_SEPARATOR . '*.jpeg')
);
$masterFiles = array_merge(
    glob(rtrim($argv[2], '/\\') . DIRECTORY_SEPARATOR . '*.jpg'),
    glob(rtrim($argv[2], '/\\') . DIRECTORY_SEPARATOR . '*.jpeg')
);
$masterSignatures = [];
foreach ($masterFiles as $file) {
    $masterSignatures[$file] = [
        'crop' => signature($file, true),
        'stretch' => signature($file, false),
    ];
}

$results = [];
foreach ($corporateFiles as $corporateFile) {
    $corporateSignature = signature($corporateFile, false);
    $matches = [];
    foreach ($masterSignatures as $masterFile => $signatures) {
        $matches[] = [
            'master' => basename($masterFile),
            'score' => min(
                distance($corporateSignature, $signatures['crop']),
                distance($corporateSignature, $signatures['stretch'])
            ),
        ];
    }
    usort($matches, static fn ($a, $b) => $a['score'] <=> $b['score']);
    $results[] = [
        'corporate' => basename($corporateFile),
        'best' => $matches[0] ?? null,
        'second' => $matches[1] ?? null,
        'margin' => isset($matches[1]) ? $matches[1]['score'] - $matches[0]['score'] : null,
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
