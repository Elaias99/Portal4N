<?php

namespace App\Services\Courier\Geo;

use Illuminate\Support\Str;
use Throwable;

/** Resume exclusivamente el archivo capturado; no consulta bultos ni reglas de pago. */
class GeolicePackageSummary
{
    public function __construct(private readonly GeoliceXlsxRows $reader) {}

    /** @return array{rows: int, packages: int, duplicates: int, without_tracking: int, statuses: list<array{status: string, packages: int}>} */
    public function read(string $path): array
    {
        $rows = $duplicates = $withoutTracking = 0;
        $seen = $statuses = [];
        $hasHeaders = false;
        $trackingIndex = $statusIndex = null;

        try {
            foreach ($this->reader->rows($path) as $row) {
                if (! $hasHeaders) {
                    if ($row['number'] !== 1) {
                        throw new GeoliceCaptureException('El Excel descargado no contiene una cabecera de paquetes en la primera fila.');
                    }
                    foreach ($row['values'] as $index => $value) {
                        $header = $this->header($value);
                        if ($header === 'seguimiento paquete') {
                            $trackingIndex = $index;
                        }
                        if ($header === 'estado de entrega') {
                            $statusIndex = $index;
                        }
                    }
                    if ($trackingIndex === null || $statusIndex === null) {
                        throw new GeoliceCaptureException('El archivo debe incluir «Seguimiento paquete» y «Estado de entrega».');
                    }
                    $hasHeaders = true;

                    continue;
                }

                if (! $row['nonempty']) {
                    continue;
                }
                $rows++;
                $tracking = trim($row['values'][$trackingIndex] ?? '');
                if ($tracking === '') {
                    $withoutTracking++;

                    continue;
                }
                if (isset($seen[$tracking])) {
                    $duplicates++;

                    continue;
                }
                $seen[$tracking] = true;
                $status = trim($row['values'][$statusIndex] ?? '') ?: 'Sin estado';
                $statuses[$status] = ($statuses[$status] ?? 0) + 1;
            }
        } catch (GeoliceCaptureException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new GeoliceCaptureException('El archivo quedó descargado, pero no se pudo leer su resumen.');
        }

        if (! $hasHeaders) {
            throw new GeoliceCaptureException('El Excel descargado no contiene una cabecera de paquetes.');
        }
        $result = [];
        foreach ($statuses as $status => $count) {
            $result[] = ['status' => (string) $status, 'packages' => $count];
        }
        usort($result, fn (array $a, array $b): int => [$b['packages'], $a['status']] <=> [$a['packages'], $b['status']]);

        return ['rows' => $rows, 'packages' => count($seen), 'duplicates' => $duplicates, 'without_tracking' => $withoutTracking, 'statuses' => $result];
    }

    /** El importador Courier existente usa las posiciones A…AE, no los nombres. */
    public function comprobarHeadersForCourier(string $path): void
    {
        $expected = [
            0 => 'Seguimiento paquete',
            10 => 'Estado de entrega',
            12 => 'Comerciante',
            13 => 'Servicio',
            18 => 'Comuna de destino',
        ];

        try {
            foreach ($this->reader->rows($path) as $row) {
                $headers = $row['values'];
                if ($row['number'] !== 1 || count($headers) !== 31 || max(array_keys($headers)) !== 30 || count(array_filter($headers, fn (string $value): bool => trim($value) !== '')) !== 31) {
                    throw new GeoliceCaptureException('Para revisar en Courier se necesita la exportación original de Geo con sus 31 columnas, de A a AE.');
                }

                foreach ($expected as $index => $label) {
                    if ($this->header($headers[$index] ?? '') !== $this->header($label)) {
                        throw new GeoliceCaptureException('Las columnas del archivo no tienen el orden que espera Courier: A «Seguimiento paquete», K «Estado de entrega», M «Comerciante», N «Servicio» y S «Comuna de destino».');
                    }
                }

                return;
            }
        } catch (GeoliceCaptureException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new GeoliceCaptureException('No se pudo comprobar la cabecera del archivo antes de revisar en Courier.');
        }

        throw new GeoliceCaptureException('El Excel descargado no contiene una cabecera de paquetes.');
    }

    private function header(string $value): string
    {
        return Str::of($value)->squish()->lower()->ascii()->toString();
    }
}
