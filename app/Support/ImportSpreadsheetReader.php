<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use SimpleXLSX;

class ImportSpreadsheetReader
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public static function read(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());
        $headers = [];
        $rows = [];

        if ($extension === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle !== false) {
                $headers = fgetcsv($handle, 0, ',') ?: [];
                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    if (count($headers) === count($row)) {
                        $rows[] = array_combine($headers, $row);
                    }
                }
                fclose($handle);
            }
        } elseif ($xlsx = SimpleXLSX::parse($path)) {
            $allRows = $xlsx->rows();
            $headers = array_shift($allRows) ?: [];
            foreach ($allRows as $row) {
                if (count($headers) === count($row)) {
                    $rows[] = array_combine($headers, $row);
                }
            }
        }

        return [
            'headers' => array_values(array_filter($headers, fn ($h) => $h !== null && $h !== '')),
            'rows'    => $rows,
        ];
    }
}
