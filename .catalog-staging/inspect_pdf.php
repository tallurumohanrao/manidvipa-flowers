<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php inspect_pdf.php input.pdf [output-directory]\n");
    exit(1);
}

$data = file_get_contents($argv[1]);
$outputDirectory = $argv[2] ?? null;
if ($data === false) {
    exit(1);
}

function ascii85(string $input): string
{
    $input = preg_replace('/\s+/', '', $input);
    $input = preg_replace('/^<~/', '', $input);
    $input = preg_replace('/~>.*$/s', '', $input);
    $result = '';
    $group = [];
    foreach (str_split($input) as $char) {
        if ($char === 'z' && $group === []) {
            $result .= "\0\0\0\0";
            continue;
        }
        $code = ord($char);
        if ($code < 33 || $code > 117) {
            continue;
        }
        $group[] = $code - 33;
        if (count($group) === 5) {
            $value = 0;
            foreach ($group as $digit) {
                $value = $value * 85 + $digit;
            }
            $result .= pack('N', $value);
            $group = [];
        }
    }
    if ($group !== []) {
        $originalCount = count($group);
        while (count($group) < 5) {
            $group[] = 84;
        }
        $value = 0;
        foreach ($group as $digit) {
            $value = $value * 85 + $digit;
        }
        $result .= substr(pack('N', $value), 0, $originalCount - 1);
    }
    return $result;
}

function streamFromObject(string $object): ?string
{
    $streamAt = strpos($object, 'stream');
    $endAt = strrpos($object, 'endstream');
    if ($streamAt === false || $endAt === false || $endAt <= $streamAt) {
        return null;
    }
    $dictionary = substr($object, 0, $streamAt);
    $start = $streamAt + strlen('stream');
    if (substr($object, $start, 2) === "\r\n") {
        $start += 2;
    } elseif (isset($object[$start]) && ($object[$start] === "\n" || $object[$start] === "\r")) {
        $start++;
    }
    $value = rtrim(substr($object, $start, $endAt - $start), "\r\n");
    if (str_contains($dictionary, '/ASCII85Decode')) {
        $value = ascii85($value);
    }
    if (str_contains($dictionary, '/FlateDecode')) {
        $inflated = @gzuncompress($value);
        if ($inflated === false) {
            $inflated = @gzinflate($value);
        }
        if ($inflated === false && strlen($value) > 2) {
            $inflated = @gzinflate(substr($value, 2));
        }
        if ($inflated === false) {
            return null;
        }
        $value = $inflated;
    }
    return $value;
}

$objects = [];
if (preg_match_all('/startxref\s+(\d+)/', $data, $startMatches) && $startMatches[1] !== []) {
    $xrefOffset = (int) end($startMatches[1]);
    $xrefText = str_replace("\r\n", "\n", substr($data, $xrefOffset));
    $xrefText = str_replace("\r", "\n", $xrefText);
    $lines = explode("\n", $xrefText);
    $offsets = [];
    for ($lineIndex = 1; $lineIndex < count($lines); $lineIndex++) {
        $line = trim($lines[$lineIndex]);
        if ($line === '' || $line === 'xref') {
            continue;
        }
        if ($line === 'trailer') {
            break;
        }
        if (!preg_match('/^(\d+)\s+(\d+)$/', $line, $section)) {
            continue;
        }
        $firstObject = (int) $section[1];
        $count = (int) $section[2];
        for ($entry = 0; $entry < $count && ++$lineIndex < count($lines); $entry++) {
            if (preg_match('/^(\d{10})\s+\d{5}\s+([nf])/', trim($lines[$lineIndex]), $xrefEntry) && $xrefEntry[2] === 'n') {
                $offsets[$firstObject + $entry] = (int) $xrefEntry[1];
            }
        }
    }
    asort($offsets);
    $ordered = array_keys($offsets);
    foreach ($ordered as $position => $number) {
        $start = $offsets[$number];
        $end = $position + 1 < count($ordered) ? $offsets[$ordered[$position + 1]] : $xrefOffset;
        $rawObject = substr($data, $start, $end - $start);
        if (preg_match('/^\s*\d+\s+\d+\s+obj(.*)endobj\s*$/s', $rawObject, $objectMatch)) {
            $objects[$number] = $objectMatch[1];
        }
    }
}

if ($objects === []) {
    preg_match_all('/(\d+)\s+(\d+)\s+obj(.*?)endobj/s', $data, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $objects[(int) $match[1]] = $match[3];
    }
}

if ($outputDirectory) {
    @mkdir($outputDirectory, 0777, true);
    foreach ($objects as $number => $object) {
        if (!preg_match('/\/Subtype\s*\/Image/', $object) || !str_contains($object, '/DCTDecode')) {
            continue;
        }
        $image = streamFromObject($object);
        if ($image === null || @getimagesizefromstring($image) === false) {
            continue;
        }
        file_put_contents($outputDirectory . DIRECTORY_SEPARATOR . sprintf('object-%04d.jpg', $number), $image);
    }
}

$pages = [];
$pageNumber = 0;
foreach ($objects as $number => $object) {
    if (!preg_match('/\/Type\s*\/Page(?!s)/', $object)) {
        continue;
    }
    $pageNumber++;
    $contents = [];
    if (preg_match('/\/Contents\s+(\d+)\s+\d+\s+R/', $object, $contentMatch)) {
        $contents[] = (int) $contentMatch[1];
    } elseif (preg_match('/\/Contents\s*\[(.*?)\]/s', $object, $contentArray)) {
        preg_match_all('/(\d+)\s+\d+\s+R/', $contentArray[1], $contentRefs);
        $contents = array_map('intval', $contentRefs[1]);
    }
    $xobjects = [];
    if (preg_match('/\/XObject\s*<<(.*?)>>/s', $object, $xobjectMatch)) {
        preg_match_all('/\/(\S+)\s+(\d+)\s+\d+\s+R/', $xobjectMatch[1], $refs, PREG_SET_ORDER);
        foreach ($refs as $ref) {
            $xobjects[$ref[1]] = (int) $ref[2];
        }
    }
    $content = '';
    foreach ($contents as $contentObject) {
        $content .= streamFromObject($objects[$contentObject] ?? '') ?? '';
        $content .= "\n";
    }
    if ($outputDirectory && $content !== '') {
        file_put_contents($outputDirectory . DIRECTORY_SEPARATOR . sprintf('page-%03d-content.txt', $pageNumber), $content);
    }
    preg_match_all('/([-+\d.]+)\s+([-+\d.]+)\s+([-+\d.]+)\s+([-+\d.]+)\s+([-+\d.]+)\s+([-+\d.]+)\s+cm\s+\/(\S+)\s+Do/s', $content, $drawMatches, PREG_SET_ORDER);
    $draws = [];
    foreach ($drawMatches as $draw) {
        $draws[] = [
            'name' => $draw[7],
            'object' => $xobjects[$draw[7]] ?? null,
            'width' => (float) $draw[1],
            'height' => (float) $draw[4],
            'x' => (float) $draw[5],
            'y' => (float) $draw[6],
        ];
    }
    $pages[] = [
        'page_object' => $number,
        'content_objects' => $contents,
        'draws' => $draws,
    ];
}

echo json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
