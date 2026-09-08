<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;

class ImportSpreadsheetReader
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public static function read(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        if (!$path || !is_readable($path)) {
            throw new \InvalidArgumentException('تعذّر الوصول إلى الملف المرفوع.');
        }

        return match ($extension) {
            'csv' => self::readCsv($path),
            'xls' => self::readXls($path),
            'xlsx', 'xlsm', 'xltx', 'xltm' => self::readXlsx($path),
            default => throw new \InvalidArgumentException('صيغة الملف غير مدعومة. استخدم CSV أو XLS أو XLSX أو XLSM أو XLTX أو XLTM.'),
        };
    }

    /**
     * Read legacy Excel 97-2003 workbooks.
     */
    private static function readXls(string $path): array
    {
        $xls = SimpleXLS::parse($path);

        if ($xls === false) {
            throw new \InvalidArgumentException(
                'تعذّرت قراءة ملف XLS: ' . (SimpleXLS::parseError() ?: 'صيغة الملف غير صالحة.')
            );
        }

        return self::rowsToAssociative($xls->rows());
    }

    /**
     * Read modern OOXML workbooks. The parser works with the OOXML container
     * used by XLSX/XLSM/XLTX/XLTM, so the filename extension is not used to
     * change the parsing logic.
     */
    private static function readXlsx(string $path): array
    {
        $xlsx = SimpleXLSX::parse($path);

        if ($xlsx === false) {
            throw new \InvalidArgumentException(
                'تعذّرت قراءة ملف Excel: ' . (SimpleXLSX::parseError() ?: 'صيغة الملف غير صالحة.')
            );
        }

        return self::rowsToAssociative($xlsx->rows());
    }

    /**
     * Read CSV files with UTF-8/UTF-16 BOM handling and common delimiters.
     * Delimiter detection supports comma, semicolon, tab and pipe.
     */
    private static function readCsv(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \InvalidArgumentException('تعذّرت قراءة ملف CSV.');
        }

        $contents = self::normalizeTextEncoding($contents);
        $contents = ltrim($contents, "\xEF\xBB\xBF");

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \InvalidArgumentException('تعذّر فتح بيانات CSV للقراءة.');
        }

        fwrite($handle, $contents);
        rewind($handle);

        $delimiter = self::detectCsvDelimiter($handle);
        rewind($handle);

        $rawRows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (self::isEmptyRow($row)) {
                continue;
            }
            $rawRows[] = $row;
        }

        fclose($handle);

        if ($rawRows === []) {
            throw new \InvalidArgumentException('ملف CSV فارغ.');
        }

        return self::rowsToAssociative($rawRows);
    }

    /**
     * @param resource $handle
     */
    private static function detectCsvDelimiter($handle): string
    {
        $candidates = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestScore = 1;

        while (($line = fgets($handle)) !== false) {
            if (trim($line) === '') {
                continue;
            }

            foreach ($candidates as $candidate) {
                $fields = str_getcsv($line, $candidate);
                $score = count($fields);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestDelimiter = $candidate;
                }
            }

            break;
        }

        return $bestDelimiter;
    }

    private static function normalizeTextEncoding(string $contents): string
    {
        if (str_starts_with($contents, "\xFF\xFE")) {
            return mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE');
        }

        if (str_starts_with($contents, "\xFE\xFF")) {
            return mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE');
        }

        if (mb_check_encoding($contents, 'UTF-8')) {
            return $contents;
        }

        $encoding = mb_detect_encoding(
            $contents,
            ['Windows-1256', 'ISO-8859-6', 'Windows-1252', 'ISO-8859-1'],
            true
        );

        return $encoding
            ? mb_convert_encoding($contents, 'UTF-8', $encoding)
            : $contents;
    }

    /**
     * Convert rows to the associative structure expected by the existing
     * import/mapping code. Rows with missing trailing cells are padded and
     * rows with extra cells are safely truncated instead of being discarded.
     *
     * @param array<int, array<int, mixed>> $rawRows
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    private static function rowsToAssociative(array $rawRows): array
    {
        if ($rawRows === []) {
            throw new \InvalidArgumentException('الملف لا يحتوي على بيانات.');
        }

        $headers = self::normalizeHeaders($rawRows[0]);
        $rows = [];

        foreach (array_slice($rawRows, 1) as $row) {
            $row = array_values($row);

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            } elseif (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            }

            if (self::isEmptyRow($row)) {
                continue;
            }

            $rows[] = array_combine($headers, $row);
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Keep all header positions stable and make duplicate headers safe.
     *
     * @param array<int, mixed> $headers
     * @return array<int, string>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        $used = [];

        foreach ($headers as $index => $header) {
            $header = trim((string) $header);
            $header = ltrim($header, "\xEF\xBB\xBF");

            if ($header === '') {
                $header = 'Column ' . ($index + 1);
            }

            $base = $header;
            $suffix = 2;

            while (isset($used[$header])) {
                $header = $base . '_' . $suffix++;
            }

            $used[$header] = true;
            $normalized[] = $header;
        }

        return $normalized;
    }

    private static function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
