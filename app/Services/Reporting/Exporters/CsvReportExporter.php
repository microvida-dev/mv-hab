<?php

namespace App\Services\Reporting\Exporters;

class CsvReportExporter
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function render(array $rows): string
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            return '';
        }

        fwrite($stream, "\xEF\xBB\xBF");

        if ($rows !== []) {
            fputcsv($stream, array_keys($rows[0]), ';', '"', '\\');
            foreach ($rows as $row) {
                fputcsv($stream, array_map([$this, 'safeCell'], array_values($row)), ';', '"', '\\');
            }
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents ?: '';
    }

    private function safeCell(mixed $value): string
    {
        $value = is_array($value) ? (json_encode($value, JSON_UNESCAPED_UNICODE) ?: '') : (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
