<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php extract_pdf_text.php input.pdf\n");
    exit(1);
}

$data = file_get_contents($argv[1]);
if ($data === false) {
    fwrite(STDERR, "Unable to read PDF.\n");
    exit(1);
}

$chunks = [$data];

function decodeAscii85(string $input): string
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

$objectBodies = [];
if (preg_match_all('/startxref\s+(\d+)/', $data, $startMatches) && $startMatches[1] !== []) {
    $xrefOffset = (int) end($startMatches[1]);
    $xrefText = str_replace(["\r\n", "\r"], "\n", substr($data, $xrefOffset));
    $xrefLines = explode("\n", $xrefText);
    $offsets = [];
    for ($lineIndex = 1; $lineIndex < count($xrefLines); $lineIndex++) {
        $line = trim($xrefLines[$lineIndex]);
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
        for ($entry = 0; $entry < $count && ++$lineIndex < count($xrefLines); $entry++) {
            if (preg_match('/^(\d{10})\s+\d{5}\s+([nf])/', trim($xrefLines[$lineIndex]), $xrefEntry) && $xrefEntry[2] === 'n') {
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
            $objectBodies[] = $objectMatch[1];
        }
    }
}
if ($objectBodies === [] && preg_match_all('/\d+\s+\d+\s+obj(.*?)endobj/s', $data, $objects)) {
    $objectBodies = $objects[1];
}

foreach ($objectBodies as $object) {
        $streamAt = strpos($object, 'stream');
        $endAt = strrpos($object, 'endstream');
        if ($streamAt === false || $endAt === false || $endAt <= $streamAt) {
            continue;
        }
        $dictionary = substr($object, 0, $streamAt);
        if (str_contains($dictionary, '/DCTDecode') || str_contains($dictionary, '/JPXDecode')) {
            continue;
        }
        $start = $streamAt + strlen('stream');
        if (substr($object, $start, 2) === "\r\n") {
            $start += 2;
        } elseif (isset($object[$start]) && ($object[$start] === "\n" || $object[$start] === "\r")) {
            $start++;
        }
        $decoded = rtrim(substr($object, $start, $endAt - $start), "\r\n");
        if (str_contains($dictionary, '/ASCII85Decode')) {
            $decoded = decodeAscii85($decoded);
        }
        if (str_contains($dictionary, '/FlateDecode')) {
            $inflated = @gzuncompress($decoded);
            if ($inflated === false) {
                $inflated = @gzinflate($decoded);
            }
            if ($inflated === false && strlen($decoded) > 2) {
                $inflated = @gzinflate(substr($decoded, 2));
            }
            if ($inflated === false) {
                continue;
            }
            $decoded = $inflated;
        }
        $chunks[] = $decoded;
}

function decodePdfString(string $value): string
{
    $value = preg_replace_callback('/\\\\([0-7]{1,3})/', static fn ($m) => chr(octdec($m[1])), $value);
    return strtr($value, [
        '\\n' => "\n", '\\r' => "\r", '\\t' => "\t", '\\b' => "\b", '\\f' => "\f",
        '\\(' => '(', '\\)' => ')', '\\\\' => '\\',
    ]);
}

$lines = [];
foreach ($chunks as $chunk) {
    if (preg_match_all('/\(((?:\\\\.|[^\\)])*)\)\s*Tj/s', $chunk, $matches)) {
        foreach ($matches[1] as $value) {
            $lines[] = decodePdfString($value);
        }
    }
    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $chunk, $arrays)) {
        foreach ($arrays[1] as $array) {
            if (preg_match_all('/\(((?:\\\\.|[^\\)])*)\)/s', $array, $parts)) {
                $line = implode('', array_map('decodePdfString', $parts[1]));
                $lines[] = $line;
            }
        }
    }
}

$clean = [];
foreach ($lines as $line) {
    $line = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $line));
    if ($line !== '' && preg_match('/[A-Za-z0-9]/', $line)) {
        $clean[] = $line;
    }
}

echo implode(PHP_EOL, $clean), PHP_EOL;
