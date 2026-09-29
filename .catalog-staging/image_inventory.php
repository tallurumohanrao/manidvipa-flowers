<?php

foreach (array_slice($argv, 1) as $pattern) {
    $files = glob($pattern, GLOB_BRACE);
    foreach ($files as $file) {
        $size = @getimagesize($file);
        if (!$size) {
            continue;
        }
        echo json_encode([
            'file' => str_replace('\\', '/', $file),
            'width' => $size[0],
            'height' => $size[1],
            'bytes' => filesize($file),
            'mime' => $size['mime'] ?? null,
        ], JSON_UNESCAPED_SLASHES), PHP_EOL;
    }
}
