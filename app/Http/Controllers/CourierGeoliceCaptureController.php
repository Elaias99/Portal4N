<?php

namespace App\Http\Controllers;

use App\Jobs\CaptureCourierGeolicePackages;
use App\Models\CourierPeriodo;
use App\Services\Courier\CourierPagoService;
use App\Services\Courier\Geo\GeoliceCaptureException;
use App\Services\Courier\Geo\GeoliceCaptureLog;
use App\Services\Courier\Geo\GeoliceCapturePresenter;
use App\Services\Courier\Geo\GeoliceCaptureStore;
use App\Services\Courier\Geo\GeolicePackageSummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CourierGeoliceCaptureController extends Controller
{
    public function saveAccount(Request $request, GeoliceCaptureStore $store): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Ingresa el correo o usuario que utilizas para entrar a Geo.',
            'password.required' => 'Ingresa la contraseña de tu cuenta de Geo.',
        ]);

        try {
            $store->saveAccount($this->userId($request), trim($validated['email']), $validated['password']);
        } catch (Throwable $exception) {
            return $this->failure($request, 'cuenta_no_guardada', $exception);
        }

        GeoliceCaptureLog::write('cuenta_guardada_sin_sesion', ['user_id' => $this->userId($request)]);

        return redirect()->route('courier.index')
            ->with('courier_geo_status', 'Cuenta guardada. La conexión con Geo se comprobará al traer los paquetes.');
    }

    public function forgetAccount(Request $request, GeoliceCaptureStore $store): RedirectResponse
    {
        try {
            $store->forgetAccount($this->userId($request));
        } catch (Throwable $exception) {
            return $this->failure($request, 'cuenta_no_desconectada', $exception);
        }

        $request->session()->forget('_old_input');
        GeoliceCaptureLog::write('cuenta_desconectada', ['user_id' => $this->userId($request)]);

        return redirect()->route('courier.index')
            ->with('courier_geo_status', 'Se olvidaron el correo, la contraseña y la sesión guardada de Geo.');
    }

    public function capture(Request $request, GeoliceCaptureStore $store): RedirectResponse
    {
        $validated = $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'periodo' => ['required', 'date_format:Y-m'],
        ], [
            'desde.required' => 'Elige desde qué día traer los paquetes.',
            'hasta.required' => 'Elige hasta qué día traer los paquetes.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'periodo.required' => 'Elige el mes de pago para la revisión.',
        ]);

        if (CarbonImmutable::parse($validated['hasta'])->greaterThan(
            CarbonImmutable::parse($validated['desde'])->addMonthsNoOverflow(2)
        )) {
            return redirect()->route('courier.index')->withErrors([
                'hasta' => 'Geo permite exportar un rango máximo de dos meses desde la fecha inicial.',
            ])->withInput($request->only('desde', 'hasta', 'periodo'));
        }

        $period = str_replace('-', '', $validated['periodo']);
        $userId = $this->userId($request);

        if ($this->closed($period)) {
            return redirect()->route('courier.index')->withErrors([
                'periodo' => 'Ese mes de pago está cerrado. Elige un mes abierto para revisar una nueva captura.',
            ])->withInput($request->only('desde', 'hasta', 'periodo'));
        }

        try {
            $capture = $store->create($userId, $validated['desde'], $validated['hasta'], $period);
        } catch (Throwable $exception) {
            return $this->failure($request, 'solicitud_no_creada', $exception);
        }

        GeoliceCaptureLog::write('solicitud_creada', [
            'capture_id' => $capture['id'], 'user_id' => $userId,
            'from' => $validated['desde'], 'to' => $validated['hasta'], 'payment_period' => $period,
        ]);

        try {
            CaptureCourierGeolicePackages::dispatch($userId, $capture['id']);
            GeoliceCaptureLog::write('solicitud_encolada', ['capture_id' => $capture['id'], 'user_id' => $userId]);
        } catch (Throwable $exception) {
            GeoliceCaptureLog::write('solicitud_no_encolada', [
                'capture_id' => $capture['id'], 'user_id' => $userId, 'exception_type' => $exception::class,
            ], 'error');
            $store->update($userId, $capture['id'], [
                'state' => 'error',
                'message' => 'No se pudo iniciar la captura. Revisa la cola de Portal4N y su registro de Geo.',
                'finished_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()->route('courier.index');
    }

    public function status(
        Request $request,
        string $capture,
        GeoliceCaptureStore $store,
        GeoliceCapturePresenter $presenter
    ): JsonResponse {
        $record = $store->find($this->userId($request), $capture);
        abort_if($record === null, 404);

        return response()->json(['capture' => $presenter->present($record)])
            ->header('Cache-Control', 'no-store, private');
    }

    public function cancel(Request $request, string $capture, GeoliceCaptureStore $store): RedirectResponse
    {
        $userId = $this->userId($request);
        abort_if($store->find($userId, $capture) === null, 404);

        try {
            $store->cancelPending($userId, $capture);
        } catch (Throwable $exception) {
            return $this->failure($request, 'solicitud_no_cancelada', $exception);
        }

        return redirect()->route('courier.index');
    }

    public function download(Request $request, string $capture, GeoliceCaptureStore $store): BinaryFileResponse
    {
        $record = $store->find($this->userId($request), $capture);
        abort_if($record === null || $record['state'] !== 'listo' || empty($record['file_name']), 404);
        $path = $store->filePath($this->userId($request), $capture);
        abort_unless(is_file($path), 404);

        return response()->download($path, $record['file_name'])
            ->setPrivate()->setMaxAge(0);
    }

    public function review(
        Request $request,
        string $capture,
        GeoliceCaptureStore $store,
        GeolicePackageSummary $summary,
        CourierPagoService $payments
    ): RedirectResponse {
        $userId = $this->userId($request);
        $record = $store->find($userId, $capture);
        abort_if($record === null, 404);

        if ($record['state'] !== 'listo') {
            return redirect()->route('courier.index')->withErrors([
                'geo' => 'Espera a que termine la captura antes de revisar sus paquetes.',
            ]);
        }

        if ($this->closed($record['payment_period'])) {
            return redirect()->route('courier.index')->withErrors([
                'periodo' => 'El mes de pago de esta captura está cerrado; no se puede cargar sobre él.',
            ]);
        }

        $path = $store->filePath($userId, $capture);
        abort_unless(is_file($path), 404);

        try {
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            $summary->comprobarHeadersForCourier($path);
            $payments->descartarRevision($request->session()->get('revisionGeolice.token'));
            $request->session()->forget('revisionGeolice');
            $revision = $payments->revisarCapturaGeolice(
                $path,
                $record['file_name'],
                $record['payment_period'],
                [
                    'capture_id' => $capture,
                    'user_id' => $userId,
                    'from' => $record['from'],
                    'to' => $record['to'],
                    'payment_period' => $record['payment_period'],
                ]
            );
        } catch (Throwable $exception) {
            return $this->failure($request, 'captura_no_revisada', $exception, $capture);
        }

        $request->session()->put('revisionGeolice', $revision);
        GeoliceCaptureLog::write('captura_revisada_sin_importar', ['capture_id' => $capture, 'user_id' => $userId]);

        return redirect()->route('courier.index');
    }

    private function userId(Request $request): int
    {
        return (int) $request->user()->getAuthIdentifier();
    }

    private function closed(string $period): bool
    {
        return CourierPeriodo::query()->where('codigo', $period)->where('estado', 'cerrado')->exists();
    }

    private function failure(
        Request $request,
        string $event,
        Throwable $exception,
        ?string $captureId = null
    ): RedirectResponse {
        GeoliceCaptureLog::write($event, [
            'user_id' => $this->userId($request), 'capture_id' => $captureId,
            'exception_type' => $exception::class,
        ], 'warning');

        return redirect()->route('courier.index')->withErrors([
            'geo' => $exception instanceof GeoliceCaptureException
                ? $exception->getMessage()
                : 'No se pudo completar la acción. Revisa el registro de Geo de Portal4N.',
        ]);
    }
}
