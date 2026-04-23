<?php

namespace App\Http\Controllers;

use App\Models\TrackingAlmacenado;
use App\Services\LatamGuidePdfParserService;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutlookMailController extends Controller
{
    public function __construct(
        private MicrosoftGraphMailService $graphService,
        private LatamGuidePdfParserService $pdfParser
    ) {
    }

    public function connect(): RedirectResponse
    {
        $authUrl = $this->graphService->getAuthorizationUrl();

        return redirect()->away($authUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('outlook-mails.index')
                ->with(
                    'warning',
                    'Microsoft devolvió un error al iniciar sesión: ' .
                    ($request->string('error_description')->toString() ?: $request->string('error')->toString())
                );
        }

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $tokens = $this->graphService->exchangeCodeForToken(
                $request->string('code')->toString()
            );

            session([
                'outlook_access_token' => $tokens['access_token'],
                'outlook_refresh_token' => $tokens['refresh_token'] ?? null,
                'outlook_token_expires_at' => now()
                    ->addSeconds((int) ($tokens['expires_in'] ?? 3600))
                    ->timestamp,
            ]);

            session()->forget('outlook_oauth_state');

            return redirect()
                ->route('outlook-mails.index')
                ->with('success', 'Cuenta de Outlook conectada correctamente.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('outlook-mails.index')
                ->with('warning', 'No se pudo completar la conexión con Outlook.');
        }
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        try {
            $isConnected = $this->graphService->hasValidSessionToken();
            $emails = [];
            $connectedUser = null;

            if ($isConnected) {
                $connectedUser = $this->graphService->getSignedInUser();

                $emails = $this->filtrarCorreosPendientes(
                    $this->graphService->listLatamCandidateEmails(
                        $filters['fecha_desde'] ?? null,
                        $filters['fecha_hasta'] ?? null
                    )
                );
            }

            return view('outlook-mails.index', [
                'emails' => $emails,
                'preview' => null,
                'selectedMessageId' => null,
                'error' => null,
                'isConnected' => $isConnected,
                'connectedUser' => $connectedUser,
                'filters' => $filters,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return view('outlook-mails.index', [
                'emails' => [],
                'preview' => null,
                'selectedMessageId' => null,
                'error' => 'No se pudieron cargar los correos.',
                'isConnected' => false,
                'connectedUser' => null,
                'filters' => $filters,
            ]);
        }
    }


    public function preview(Request $request, string $messageId): View
    {
        $filters = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        try {
            $emails = $this->filtrarCorreosPendientes(
                $this->graphService->listLatamCandidateEmails(
                    $filters['fecha_desde'] ?? null,
                    $filters['fecha_hasta'] ?? null
                )
            );

            $attachment = $this->graphService->downloadFirstPdfAttachment($messageId);
            $preview = $this->pdfParser->parsePdfBinary($attachment['content']);

            return view('outlook-mails.index', [
                'emails' => $emails,
                'preview' => $preview,
                'selectedMessageId' => $messageId,
                'error' => null,
                'isConnected' => true,
                'filters' => $filters,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return view('outlook-mails.index', [
                'emails' => [],
                'preview' => null,
                'selectedMessageId' => $messageId,
                'error' => 'No se pudo procesar el adjunto PDF del correo. ' . $e->getMessage(),
                'isConnected' => true,
            ]);
        }
    }




    public function store(Request $request, string $messageId): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'prefijo' => ['required', 'regex:/^\d{3}$/'],
                'codigo_tracking' => ['required', 'regex:/^\d{8}$/'],
                'fecha_proceso' => ['required', 'date'],
                'destino' => ['required', 'string'],
            ]);

            $destinosPermitidos = $this->obtenerDestinosPermitidos();

            if (!in_array($validated['destino'], $destinosPermitidos, true)) {
                return redirect()
                    ->route('outlook-mails.index')
                    ->with('warning', 'El destino obtenido desde el PDF no es válido para guardar.');
            }

            $tracking = TrackingAlmacenado::firstOrCreate([
                'prefijo' => $validated['prefijo'],
                'codigo_tracking' => $validated['codigo_tracking'],
                'fecha_proceso' => $validated['fecha_proceso'],
                'destino' => $validated['destino'],
            ]);

            return redirect()
                ->route('outlook-mails.index')
                ->with(
                    $tracking->wasRecentlyCreated ? 'success' : 'warning',
                    $tracking->wasRecentlyCreated
                        ? 'Tracking guardado correctamente desde Outlook.'
                        : 'Ese tracking ya existía en el sistema.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('outlook-mails.index')
                ->with('warning', 'No se pudo guardar el tracking desde el correo.');
        }
    }

    public function disconnect(): RedirectResponse
    {
        session()->forget([
            'outlook_access_token',
            'outlook_refresh_token',
            'outlook_token_expires_at',
            'outlook_oauth_state',
        ]);

        return redirect()
            ->route('outlook-mails.index')
            ->with('success', 'La cuenta de Outlook fue desconectada.');
    }

    private function obtenerDestinosPermitidos(): array
    {
        return [
            'SCL ARICA',
            'SCL IQUIQUE',
            'SCL ANTOFAGASTA',
            'SCL CALAMA',
            'SCL PUNTA ARENAS',
            'SCL BALMACEDA',
            'SCL ISLA DE PASCUA',
            'OTRO',
        ];
    }

    private function filtrarCorreosPendientes(array $emails): array
    {
        $pendientes = [];

        foreach ($emails as $email) {
            try {
                $attachment = $this->graphService->downloadFirstPdfAttachment($email['id']);
                $preview = $this->pdfParser->parsePdfBinary($attachment['content']);

                if (!$this->trackingYaExiste($preview)) {
                    $pendientes[] = $email;
                }
            } catch (\Throwable $e) {
                report($e);

                // Si no se puede parsear o validar, lo dejamos visible
                $pendientes[] = $email;
            }
        }

        return array_values($pendientes);
    }

    private function trackingYaExiste(array $preview): bool
    {
        if (
            blank($preview['prefijo'] ?? null) ||
            blank($preview['codigo_tracking'] ?? null) ||
            blank($preview['fecha_proceso'] ?? null) ||
            blank($preview['destino_formulario'] ?? null)
        ) {
            return false;
        }

        return TrackingAlmacenado::query()
            ->where('prefijo', $preview['prefijo'])
            ->where('codigo_tracking', $preview['codigo_tracking'])
            ->whereDate('fecha_proceso', $preview['fecha_proceso'])
            ->where('destino', $preview['destino_formulario'])
            ->exists();
    }


}