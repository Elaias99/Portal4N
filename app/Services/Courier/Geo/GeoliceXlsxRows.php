<?php

namespace App\Services\Courier\Geo;

use Generator;
use XMLReader;
use ZipArchive;

/** Lee la primera hoja de un XLSX sin cargar sus celdas completas en memoria. */
class GeoliceXlsxRows
{
    /** @return Generator<int, array{number: int, values: array<int, string>, nonempty: bool}> */
    public function rows(string $path): Generator
    {
        $zip = new ZipArchive;
        $opened = $zip->open($path, ZipArchive::RDONLY);
        if ($opened !== true) {
            GeoliceCaptureLog::write('resumen_excel_no_abierto', ['zip_error_code' => $opened], 'warning');
            throw new GeoliceCaptureException('No se pudo abrir el Excel descargado para preparar el resumen. El archivo se conserva.');
        }

        $previousErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $reader = null;

        try {
            $sheetPath = $this->firstSheetPath($zip);
            $sharedStrings = $this->sharedStrings($path, $zip);
            $reader = $this->open($path, $sheetPath);
            $number = 0;

            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $number = (int) ($reader->getAttribute('r') ?? $number + 1);
                $depth = $reader->depth;
                $values = [];
                $nonempty = false;

                if (! $reader->isEmptyElement) {
                    while ($reader->read()) {
                        if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'row' && $reader->depth === $depth) {
                            break;
                        }
                        if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'c') {
                            continue;
                        }

                        $reference = (string) $reader->getAttribute('r');
                        if (! preg_match('/^([A-Z]+)[1-9][0-9]*$/i', $reference, $matches)) {
                            throw new GeoliceCaptureException('El Excel contiene una celda sin una posición válida.');
                        }

                        $index = $this->columnIndex(strtoupper($matches[1]));
                        $value = $this->cellValue($reader, $sharedStrings);
                        $values[$index] = $value;
                        $nonempty = $nonempty || trim($value) !== '';
                    }
                }

                yield ['number' => $number, 'values' => $values, 'nonempty' => $nonempty];
            }

            if (libxml_get_errors() !== []) {
                throw new GeoliceCaptureException('La hoja de paquetes del Excel está incompleta o dañada.');
            }
        } finally {
            $reader?->close();
            $zip->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = $this->xml($zip, 'xl/workbook.xml');
        $sheets = $workbook->xpath('/*[local-name()="workbook"]/*[local-name()="sheets"]/*[local-name()="sheet"]');
        if (empty($sheets)) {
            throw new GeoliceCaptureException('El Excel descargado no contiene una hoja de paquetes.');
        }

        $attributes = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = (string) ($attributes['id'] ?? '');
        $relationships = $this->xml($zip, 'xl/_rels/workbook.xml.rels');

        foreach ($relationships->xpath('/*[local-name()="Relationships"]/*[local-name()="Relationship"]') ?: [] as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }
            if ((string) $relationship['TargetMode'] === 'External') {
                break;
            }

            $target = (string) $relationship['Target'];
            $segments = [];
            foreach (explode('/', str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target) as $segment) {
                if ($segment === '' || $segment === '.') {
                    continue;
                }
                if ($segment === '..') {
                    array_pop($segments);
                } else {
                    $segments[] = $segment;
                }
            }
            $sheetPath = implode('/', $segments);

            if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $sheetPath) && $zip->locateName($sheetPath) !== false) {
                return $sheetPath;
            }

            break;
        }

        throw new GeoliceCaptureException('No se encontró la hoja de paquetes dentro del Excel descargado.');
    }

    private function xml(ZipArchive $zip, string $entry): \SimpleXMLElement
    {
        $contents = $zip->getFromName($entry);
        $xml = $contents === false ? false : simplexml_load_string($contents, \SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false) {
            throw new GeoliceCaptureException('El Excel descargado tiene una estructura incompleta.');
        }

        return $xml;
    }

    /** @return list<string> */
    private function sharedStrings(string $path, ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $reader = $this->open($path, 'xl/sharedStrings.xml');
        $strings = [];

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                    continue;
                }

                $depth = $reader->depth;
                $text = '';
                if (! $reader->isEmptyElement) {
                    while ($reader->read()) {
                        if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'si' && $reader->depth === $depth) {
                            break;
                        }
                        if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 't') {
                            $text .= $reader->readString();
                        }
                    }
                }
                $strings[] = $text;
            }
        } finally {
            $reader->close();
        }

        return $strings;
    }

    /** @param list<string> $sharedStrings */
    private function cellValue(XMLReader $reader, array $sharedStrings): string
    {
        $type = $reader->getAttribute('t');
        $depth = $reader->depth;
        $value = '';
        $inline = '';
        if ($reader->isEmptyElement) {
            return '';
        }

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'c' && $reader->depth === $depth) {
                break;
            }
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->localName === 'v') {
                $value = $reader->readString();
            } elseif ($reader->localName === 't') {
                $inline .= $reader->readString();
            }
        }

        if ($type === 's') {
            if (! ctype_digit($value) || ! array_key_exists((int) $value, $sharedStrings)) {
                throw new GeoliceCaptureException('El Excel contiene un texto de paquete que no se pudo recuperar.');
            }

            return $sharedStrings[(int) $value];
        }

        return $type === 'inlineStr' ? $inline : $value;
    }

    private function open(string $path, string $entry): XMLReader
    {
        $reader = new XMLReader;
        if (! @$reader->open('zip://'.str_replace('\\', '/', $path).'#'.$entry, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new GeoliceCaptureException('No se pudo abrir la hoja de paquetes del Excel.');
        }
        $reader->setParserProperty(XMLReader::LOADDTD, false);
        $reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);

        return $reader;
    }

    private function columnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split($column) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }
}
