<?php

namespace App\Jobs;

use App\Services\Courier\Geo\GeoliceCaptureLog;
use App\Services\Courier\Geo\GeoliceCaptureService;
use App\Services\Courier\Geo\GeoliceCaptureStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CaptureCourierGeolicePackages implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public function __construct(public int $userId, public string $captureId)
    {
        $this->onConnection('courier-geo')->onQueue('courier-geo');
    }

    public function handle(GeoliceCaptureService $service): void
    {
        $service->capture($this->userId, $this->captureId);
    }

    public function failed(?Throwable $exception): void
    {
        GeoliceCaptureLog::write('tarea_interrumpida', [
            'capture_id' => $this->captureId,
            'user_id' => $this->userId,
            'exception_type' => $exception ? $exception::class : null,
        ], 'error');
        $store = app(GeoliceCaptureStore::class);
        if ($store->find($this->userId, $this->captureId) !== null) {
            $store->update($this->userId, $this->captureId, [
                'state' => 'error',
                'message' => 'La tarea de captura se interrumpió. Revisa las notificaciones de Geo antes de solicitar otra exportación.',
                'finished_at' => now()->toIso8601String(),
            ]);
        }
    }
}
