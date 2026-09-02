<?php

namespace App\Support\Concerns;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

/**
 * Shared CSV/XLSX reading helpers for Livewire bulk-import components.
 */
trait ImportsTabularFiles
{
    /** @return array<int, array<int, string|int|float|null>> */
    private function readImportRows(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        throw_unless($path, ValidationException::withMessages(['importFile' => 'The uploaded file could not be read.']));

        return strtolower($file->getClientOriginalExtension()) === 'xlsx'
            ? $this->readXlsxRows($path)
            : $this->readCsvRows($path);
    }

    /** @return array<int, array<int, string|null>> */
    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        throw_unless($handle, ValidationException::withMessages(['importFile' => 'The CSV file could not be opened.']));

        $rows = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $rows[] = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /** @return array<int, array<int, string|int|float|null>> */
    private function readXlsxRows(string $path): array
    {
        throw_unless(class_exists(ZipArchive::class), ValidationException::withMessages([
            'importFile' => 'Excel imports require the PHP ZIP extension.',
        ]));

        $archive = new ZipArchive;
        throw_unless($archive->open($path) === true, ValidationException::withMessages([
            'importFile' => 'The Excel file could not be opened. Upload a valid .xlsx file.',
        ]));

        try {
            $sheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            throw_unless($sheet !== false, ValidationException::withMessages([
                'importFile' => 'The Excel file does not contain a first worksheet.',
            ]));

            $sharedStrings = $this->xlsxSharedStrings($archive->getFromName('xl/sharedStrings.xml') ?: null);
            $xml = simplexml_load_string($sheet);
            throw_unless($xml !== false, ValidationException::withMessages([
                'importFile' => 'The first worksheet could not be read.',
            ]));

            $rows = [];
            foreach ($xml->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
                $values = [];

                foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    $reference = (string) $cell['r'];
                    $column = $this->xlsxColumnIndex((string) preg_replace('/\d+/', '', $reference));
                    $type = (string) $cell['t'];
                    $valueNode = $cell->xpath('./*[local-name()="v"]')[0] ?? null;
                    $value = $valueNode === null ? null : (string) $valueNode;

                    if ($type === 's' && $value !== null) {
                        $value = $sharedStrings[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]') ?: []));
                    }

                    $values[$column] = $value;
                }

                if ($values !== []) {
                    ksort($values);
                    $rows[] = $values;
                }
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }

    /** @return array<int, string> */
    private function xlsxSharedStrings(?string $xml): array
    {
        if (! $xml) {
            return [];
        }

        $document = simplexml_load_string($xml);
        if ($document === false) {
            return [];
        }

        $strings = [];
        foreach ($document->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $strings[] = implode('', array_map('strval', $item->xpath('.//*[local-name()="t"]') ?: []));
        }

        return $strings;
    }

    private function xlsxColumnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split(strtoupper($column)) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    private function normaliseImportHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header)) ?? '';

        return strtolower(str_replace([' ', '-'], '_', $header));
    }

    private function normaliseImportDate(string $value): ?string
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            if (is_numeric($value) && (float) $value > 20_000) {
                return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
            }

            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function normaliseImportBoolean(string $value): ?bool
    {
        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'y' => true,
            '0', 'false', 'no', 'n' => false,
            default => null,
        };
    }
}
