<?php

function pdfNormalizeText(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/[^\P{C}\n\t]/u', '', $text) ?? $text;
    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    if ($converted !== false) {
        $text = $converted;
    }
    return $text;
}

function pdfEscape(string $text): string
{
    return str_replace(
        ['\\', '(', ')'],
        ['\\\\', '\\(', '\\)'],
        pdfNormalizeText($text)
    );
}

function buildSimpleTextPdf(array $lines, string $title = 'Document'): string
{
    $lines = array_map(static function ($line) {
        return is_scalar($line) ? (string)$line : '';
    }, $lines);

    if (!$lines) {
        $lines = ['No data'];
    }

    $lineHeight = 14;
    $startY = 800;
    $minY = 40;

    $pages = [];
    $current = [];
    $y = $startY;
    foreach ($lines as $line) {
        if ($y < $minY) {
            $pages[] = $current;
            $current = [];
            $y = $startY;
        }
        $current[] = $line;
        $y -= $lineHeight;
    }
    if ($current) {
        $pages[] = $current;
    }

    $pageCount = count($pages);
    $objects = [];
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

    $pageStart = 4;
    $contentStart = $pageStart + $pageCount;
    $kids = [];

    for ($i = 0; $i < $pageCount; $i++) {
        $pageObj = $pageStart + $i;
        $contentObj = $contentStart + $i;
        $kids[] = $pageObj . ' 0 R';

        $commands = [];
        $commands[] = 'BT';
        $commands[] = '/F1 10 Tf';
        $commands[] = '40 800 Td';
        foreach ($pages[$i] as $line) {
            $commands[] = '(' . pdfEscape($line) . ') Tj';
            $commands[] = '0 -14 Td';
        }
        $commands[] = 'ET';
        $stream = implode("\n", $commands) . "\n";

        $objects[$pageObj] = '<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 3 0 R >> >> /MediaBox [0 0 595 842] /Contents ' . $contentObj . ' 0 R >>';
        $objects[$contentObj] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
    }

    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';

    ksort($objects);
    $maxObj = max(array_keys($objects));

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    for ($i = 1; $i <= $maxObj; $i++) {
        $objBody = $objects[$i] ?? '';
        $offsets[$i] = strlen($pdf);
        $pdf .= $i . " 0 obj\n" . $objBody . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . ($maxObj + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $maxObj; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . ($maxObj + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

