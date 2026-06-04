<?php

namespace App\Helpers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportHelper
{
    /**
     * Stream a CSV file download response.
     *
     * @param string $filename Download filename (e.g. soa-export-2026-06-01.csv)
     * @param array<int, string> $headers Column header labels
     * @param iterable<int, array<int, mixed>> $rows Row data matching header order
     */
    public static function streamDownload(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Format an array of IDs for CSV output.
     *
     * @param array<int, mixed>|null $ids
     */
    public static function formatIds(?array $ids): string
    {
        if (empty($ids)) {
            return '';
        }

        return implode(',', array_map('strval', $ids));
    }

    /**
     * Format a boolean for CSV output.
     */
    public static function formatBool(?bool $value): string
    {
        if ($value === null) {
            return '';
        }

        return $value ? 'Yes' : 'No';
    }

    /**
     * Build a dated export filename.
     */
    public static function datedFilename(string $prefix): string
    {
        return $prefix . '-' . now()->format('Y-m-d') . '.csv';
    }
}
