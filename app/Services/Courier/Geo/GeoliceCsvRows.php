<?php

namespace App\Services\Courier\Geo;

use Generator;

/** Lee el CSV de Geo fila por fila, sin cargarlo completo en memoria. */
class GeoliceCsvRows
{
    /** @return Generator<int, array{number: int, values: array<int, string>, nonempty: bool}> */
    public function rows(string $path): Generator
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            GeoliceCaptureLog::write('resumen_csv_no_abierto', [], 'warning');
            throw new GeoliceCaptureException('No se pudo abrir el CSV descargado para preparar el resumen. El archivo se conserva.');
        }

        try {
            $firstLine = (string) fgets($handle);
            $delimiter = $this->delimiter($firstLine);
            // Geo antepone el BOM de UTF-8; si se lee con él, la primera columna conserva sus comillas.
            fseek($handle, str_starts_with($firstLine, "\xEF\xBB\xBF") ? 3 : 0);
            $number = 0;

            while (($fields = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $number++;
                $values = array_map(fn (?string $value): string => (string) $value, $fields);

                yield [
                    'number' => $number,
                    'values' => $values,
                    'nonempty' => array_filter($values, fn (string $value): bool => trim($value) !== '') !== [],
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    /** Geo separa con coma; se acepta punto y coma por si el archivo pasó por Excel. */
    private function delimiter(string $firstLine): string
    {
        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }
}
