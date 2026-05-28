<?php

namespace App\Services;

class SimplePdfExportService
{
    public function render(array $document): string
    {
        $lines = $this->buildLines($document);
        $pages = array_chunk($lines, 44);
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = $this->buildPagesObject(count($pages));
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

        $pageObjectNumbers = [];
        $contentObjectNumbers = [];
        $nextObjectNumber = 4;

        foreach ($pages as $pageLines) {
            $pageObjectNumbers[] = $nextObjectNumber++;
            $contentObjectNumbers[] = $nextObjectNumber++;
        }

        foreach ($pages as $index => $pageLines) {
            $objects[] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>',
                $contentObjectNumbers[$index],
            );
            $content = $this->buildPageContent($pageLines);
            $objects[] = sprintf(
                "<< /Length %d >>\nstream\n%s\nendstream",
                strlen($content),
                $content,
            );
        }

        return $this->compilePdf($objects);
    }

    protected function buildLines(array $document): array
    {
        $lines = [
            $document['title'],
            str_repeat('=', min(strlen($document['title']), 90)),
        ];

        foreach ($document['meta'] ?? [] as $label => $value) {
            $lines[] = $this->truncate(sprintf('%s: %s', $label, $value), 96);
        }

        foreach ($document['sections'] ?? [] as $section) {
            $lines[] = '';
            $lines[] = $this->truncate(sprintf('[%s]', $section['title']), 96);

            if (!empty($section['columns'])) {
                $widths = $this->calculateColumnWidths($section['columns']);
                $lines[] = $this->formatRow($section['columns'], $widths);
                $lines[] = $this->formatSeparator($widths);

                foreach ($section['rows'] as $row) {
                    $lines[] = $this->formatRow($row, $widths);
                }
            }
        }

        return array_map(fn (string $line) => $this->truncate($line, 96), $lines);
    }

    protected function buildPagesObject(int $pageCount): string
    {
        $kids = [];
        $objectNumber = 4;

        for ($index = 0; $index < $pageCount; $index++) {
            $kids[] = sprintf('%d 0 R', $objectNumber);
            $objectNumber += 2;
        }

        return sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), $pageCount);
    }

    protected function buildPageContent(array $lines): string
    {
        $streamLines = [
            'BT',
            '/F1 10 Tf',
            '14 TL',
            '40 800 Td',
        ];

        foreach ($lines as $line) {
            $streamLines[] = sprintf('(%s) Tj T*', $this->escapePdfText($line));
        }

        $streamLines[] = 'ET';

        return implode("\n", $streamLines);
    }

    protected function compilePdf(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $index + 1, $object);
        }

        $xrefOffset = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n", count($objects) + 1);
        $pdf .= "0000000000 65535 f \n";

        for ($index = 1; $index <= count($objects); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }

        $pdf .= sprintf(
            "trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF",
            count($objects) + 1,
            $xrefOffset,
        );

        return $pdf;
    }

    protected function calculateColumnWidths(array $columns): array
    {
        $count = max(1, count($columns));
        $separatorWidth = ($count - 1) * 3;
        $usableWidth = max(20, 96 - $separatorWidth);
        $baseWidth = (int) floor($usableWidth / $count);

        return array_fill(0, $count, max(10, min($baseWidth, 32)));
    }

    protected function formatRow(array $row, array $widths): string
    {
        $cells = [];

        foreach ($widths as $index => $width) {
            $value = $row[$index] ?? '';
            $cells[] = str_pad($this->truncate((string) $value, $width), $width);
        }

        return rtrim(implode(' | ', $cells));
    }

    protected function formatSeparator(array $widths): string
    {
        return implode('-+-', array_map(fn (int $width) => str_repeat('-', $width), $widths));
    }

    protected function truncate(string $value, int $limit): string
    {
        $normalized = preg_replace('/\s+/', ' ', $value) ?? '';

        if (strlen($normalized) <= $limit) {
            return $normalized;
        }

        return substr($normalized, 0, max(0, $limit - 3)).'...';
    }

    protected function escapePdfText(string $value): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\(', '\)'],
            $value,
        );
    }
}
