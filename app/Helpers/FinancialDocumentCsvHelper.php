<?php

namespace App\Helpers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialDocumentCsvHelper
{
    /**
     * Stream CSV built by a writer callback.
     */
    public static function streamDownload(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function writeSectionTitle($handle, string $title): void
    {
        fputcsv($handle, [$title]);
    }

    public static function writeBlankRow($handle): void
    {
        fputcsv($handle, []);
    }

    /**
     * @param array<int, array{0: string, 1: string|int|float|null}> $rows Label/value pairs
     */
    public static function writeKeyValueRows($handle, array $rows): void
    {
        fputcsv($handle, ['Field', 'Value']);
        foreach ($rows as $row) {
            fputcsv($handle, [$row[0], self::stringify($row[1] ?? '')]);
        }
    }

    /**
     * @param array<int, string> $headers
     * @param iterable<int, array<int, mixed>> $rows
     */
    public static function writeTable($handle, array $headers, iterable $rows): void
    {
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $index => $header) {
                $line[] = self::stringify(is_array($row) ? ($row[$index] ?? $row[$header] ?? '') : '');
            }
            if (is_array($row) && array_is_list($row) && count($row) === count($headers)) {
                $line = array_map([self::class, 'stringify'], $row);
            } elseif (is_array($row)) {
                $line = [];
                foreach ($headers as $header) {
                    $line[] = self::stringify($row[$header] ?? '');
                }
            }
            fputcsv($handle, $line);
        }
    }

    public static function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return implode(' | ', array_map([self::class, 'stringify'], $value));
        }

        return (string) $value;
    }

    public static function sanitizeFilename(string $name, string $fallback): string
    {
        $base = trim($name) !== '' ? $name : $fallback;
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base);

        return trim($safe, '-') ?: $fallback;
    }
}
