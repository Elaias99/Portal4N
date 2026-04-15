<?php

namespace App\Http\Controllers;

use App\Models\TrackingAlmacenado;
use App\Services\LatamTracking\LatamTrackingHtmlParser;
use App\Services\LatamTracking\LatamTrackingClient;
use App\Services\LatamTracking\LatamTrackingSnapshotService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Throwable;

class LatamTrackingController extends Controller
{
    private const DEFAULT_DOC_TYPE = 'SO';

    public function __construct(
        private LatamTrackingHtmlParser $htmlParser,
        private LatamTrackingClient $client,
        private LatamTrackingSnapshotService $snapshotService,
    ) {
    }

    public function index(Request $request)
    {
        $filters = [
            'prefijo' => $request->input('prefijo'),
            'codigo_tracking' => $request->input('codigo_tracking'),
            'destino' => $request->input('destino'),
            'fecha_proceso' => $request->input('fecha_proceso'),
        ];

        $hasFilters = filled($filters['prefijo'])
            || filled($filters['codigo_tracking'])
            || filled($filters['destino'])
            || filled($filters['fecha_proceso']);

        if (!$hasFilters) {
            $today = now()->toDateString();

            $request->merge([
                'fecha_proceso' => $today,
            ]);

            $filters['fecha_proceso'] = $today;
        }

        $rows = $this->buildRowsFromFilters($filters);

        return view('latam-tracking.index', [
            'rows' => $rows,
            'trackingLookup' => null,
            'trackingResult' => null,
            'trackingError' => null,

            // nuevos datos de respaldo / snapshot
            'trackingEstadoActual' => null,
            'trackingConsulta' => null,
            'trackingCambioDetectado' => false,
            'trackingFallbackDisponible' => false,
            'trackingPersisted' => false,
        ]);
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'tracking_prefijo' => ['required', 'regex:/^\d{3}$/'],
            'tracking_codigo_tracking' => ['required', 'regex:/^\d{8}$/'],
            'tracking_doc_type' => ['nullable', 'string', 'max:10'],
            'filter_prefijo' => ['nullable', 'string', 'max:50'],
            'filter_codigo_tracking' => ['nullable', 'string', 'max:50'],
            'filter_destino' => ['nullable', 'string', 'max:100'],
            'filter_fecha_proceso' => ['nullable', 'date'],
        ]);

        $prefix = trim((string) $validated['tracking_prefijo']);
        $code = trim((string) $validated['tracking_codigo_tracking']);
        $docType = trim((string) ($validated['tracking_doc_type'] ?? self::DEFAULT_DOC_TYPE));

        if ($docType === '') {
            $docType = self::DEFAULT_DOC_TYPE;
        }

        $filters = [
            'prefijo' => $validated['filter_prefijo'] ?? null,
            'codigo_tracking' => $validated['filter_codigo_tracking'] ?? null,
            'destino' => $validated['filter_destino'] ?? null,
            'fecha_proceso' => $validated['filter_fecha_proceso'] ?? null,
        ];

        $hasFilters = filled($filters['prefijo'])
            || filled($filters['codigo_tracking'])
            || filled($filters['destino'])
            || filled($filters['fecha_proceso']);

        if (!$hasFilters) {
            $filters['fecha_proceso'] = now()->toDateString();

            $request->merge([
                'filter_fecha_proceso' => $filters['fecha_proceso'],
            ]);
        }

        $rows = $this->buildRowsFromFilters($filters);

        $trackingLookup = [
            'prefix' => $prefix,
            'code' => $code,
            'document_type' => $docType,
        ];

        $trackingResult = null;
        $trackingError = null;

        $trackingEstadoActual = null;
        $trackingConsulta = null;
        $trackingCambioDetectado = false;
        $trackingFallbackDisponible = false;
        $trackingPersisted = false;

        try {
            $result = $this->snapshotService->consultarYPersistir(
                prefix: $prefix,
                code: $code,
                docType: $docType,
            );

            $trackingLookup = $result['trackingLookup'] ?? $trackingLookup;
            $trackingResult = $result['trackingResult'] ?? null;
            $trackingError = $result['trackingError'] ?? null;

            $trackingEstadoActual = $result['estadoActual'] ?? null;
            $trackingConsulta = $result['consulta'] ?? null;
            $trackingCambioDetectado = $result['cambioDetectado'] ?? false;
            $trackingFallbackDisponible = $result['fallbackDisponible'] ?? false;
            $trackingPersisted = $result['persisted'] ?? false;
        } catch (ConnectionException $e) {
            $trackingError = 'LATAM no respondió a tiempo. Intenta nuevamente.';
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $status = $e->response?->status();

            if (in_array($status, [502, 503, 504], true)) {
                $trackingError = 'LATAM está temporalmente inestable. Intenta nuevamente.';
            } else {
                $trackingError = 'LATAM devolvió un error al consultar la guía.';
            }
        } catch (\RuntimeException $e) {
            $trackingError = $e->getMessage();
        } catch (Throwable $e) {
            $trackingError = 'No fue posible consultar LATAM Cargo en este momento.';
        }

        return view('latam-tracking.index', [
            'rows' => $rows,
            'trackingLookup' => $trackingLookup,
            'trackingResult' => $trackingResult,
            'trackingError' => $trackingError,

            // nuevos datos de respaldo / snapshot
            'trackingEstadoActual' => $trackingEstadoActual,
            'trackingConsulta' => $trackingConsulta,
            'trackingCambioDetectado' => $trackingCambioDetectado,
            'trackingFallbackDisponible' => $trackingFallbackDisponible,
            'trackingPersisted' => $trackingPersisted,
        ]);
    }

    private function buildRowsFromFilters(array $filters)
    {
        $query = TrackingAlmacenado::query();

        $prefijo = trim((string) ($filters['prefijo'] ?? ''));
        $codigoTracking = trim((string) ($filters['codigo_tracking'] ?? ''));
        $destino = trim((string) ($filters['destino'] ?? ''));
        $fechaProceso = $filters['fecha_proceso'] ?? null;

        if ($prefijo !== '') {
            $query->where('prefijo', 'like', '%' . $prefijo . '%');
        }

        if ($codigoTracking !== '') {
            $query->where('codigo_tracking', 'like', '%' . $codigoTracking . '%');
        }

        if ($destino !== '') {
            $query->where('destino', 'like', '%' . $destino . '%');
        }

        if (!empty($fechaProceso)) {
            $query->whereDate('fecha_proceso', $fechaProceso);
        }

        return $query->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'prefix' => $item->prefijo,
                    'code' => $item->codigo_tracking,
                    'destino' => $item->destino,
                    'fecha_proceso' => $item->fecha_proceso,
                    'url' => $this->buildLatamPublicUrl(
                        prefix: (string) $item->prefijo,
                        code: (string) $item->codigo_tracking,
                        docType: self::DEFAULT_DOC_TYPE,
                    ),
                ];
            });
    }

    private function buildLatamPublicUrl(string $prefix, string $code, string $docType = self::DEFAULT_DOC_TYPE): string
    {
        return 'https://www.latamcargo.com/en/trackshipment?docNumber='
            . urlencode($code)
            . '&docPrefix='
            . urlencode($prefix)
            . '&soType='
            . urlencode($docType);
    }
}