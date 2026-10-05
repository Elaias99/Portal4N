<?php

namespace App\Services\Courier\Geo;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeoliceCaptureStore
{
    private const ACTIVE_STATES = ['pendiente', 'conectando', 'preparando', 'esperando', 'descargando', 'leyendo'];

    /** @return array<string, mixed>|null */
    public function latest(int $userId): ?array
    {
        $latest = $this->read($this->folder($userId).'/ultima.json');

        return isset($latest['id']) ? $this->find($userId, $latest['id']) : null;
    }

    /** @return array<string, mixed>|null */
    public function find(int $userId, string $id): ?array
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        $capture = $this->read($this->folder($userId).'/'.$id.'/solicitud.json');

        return $capture !== null && ($capture['id'] ?? null) === $id && (int) ($capture['user_id'] ?? 0) === $userId ? $capture : null;
    }

    /** @param array<string, mixed>|null $capture */
    public function isActive(?array $capture): bool
    {
        return $capture !== null && in_array($capture['state'], self::ACTIVE_STATES, true);
    }

    /** @return array<string, mixed> */
    public function create(int $userId, string $from, string $to, string $paymentPeriod): array
    {
        if (! preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $paymentPeriod)) {
            throw new GeoliceCaptureException('Elige un mes de pago válido antes de solicitar los paquetes.');
        }

        return $this->locked($userId, function () use ($userId, $from, $to, $paymentPeriod): array {
            if ($this->isActive($this->latest($userId))) {
                throw new GeoliceCaptureException('Ya tienes una captura en curso. Espera su resultado antes de solicitar otra.');
            }
            if ($this->account($userId) === null) {
                throw new GeoliceCaptureException('Conecta tu cuenta de Geo antes de solicitar los paquetes.');
            }

            $capture = [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'from' => $from,
                'to' => $to,
                'payment_period' => $paymentPeriod,
                'state' => 'pendiente',
                'message' => 'Solicitud recibida. La captura comenzará en segundo plano.',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
                'started_at' => null,
                'finished_at' => null,
                'summary' => null,
                'file_name' => null,
            ];
            $this->write($this->folder($userId).'/'.$capture['id'].'/solicitud.json', $capture);
            $this->write($this->folder($userId).'/ultima.json', ['id' => $capture['id']]);

            return $capture;
        });
    }

    /** @param array<string, mixed> $values */
    public function update(int $userId, string $id, array $values): void
    {
        $this->locked($userId, function () use ($userId, $id, $values): void {
            $capture = $this->find($userId, $id);
            if ($capture === null) {
                throw new GeoliceCaptureException('No se encontró la solicitud de captura.');
            }
            $this->write($this->folder($userId).'/'.$id.'/solicitud.json', [
                ...$capture,
                ...$values,
                'updated_at' => now()->toIso8601String(),
            ]);
        });
    }

    /** @return array<string, mixed>|null */
    public function claim(int $userId, string $id): ?array
    {
        return $this->locked($userId, function () use ($userId, $id): ?array {
            $capture = $this->find($userId, $id);
            if ($capture === null || $capture['state'] !== 'pendiente') {
                return null;
            }
            $capture = [
                ...$capture,
                'state' => 'conectando',
                'started_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
                'message' => 'Conectando con Geo y comprobando la sesión…',
            ];
            $this->write($this->folder($userId).'/'.$id.'/solicitud.json', $capture);

            return $capture;
        });
    }

    public function cancelPending(int $userId, string $id): void
    {
        $this->locked($userId, function () use ($userId, $id): void {
            $capture = $this->find($userId, $id);
            if ($capture === null || $capture['state'] !== 'pendiente') {
                throw new GeoliceCaptureException('La captura ya comenzó. Espera su resultado antes de solicitar otra.');
            }
            $this->write($this->folder($userId).'/'.$id.'/solicitud.json', [
                ...$capture,
                'state' => 'cancelado',
                'message' => 'Solicitud cancelada antes de conectar con Geo.',
                'updated_at' => now()->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
            ]);
        });
    }

    /** @return array{email: string, password: string, cookies: list<array<string, mixed>>}|null */
    public function account(int $userId): ?array
    {
        $disk = Storage::disk('courier-geo');
        $path = $this->folder($userId).'/cuenta.enc';
        if (! $disk->exists($path)) {
            return null;
        }

        try {
            $account = json_decode(Crypt::decryptString($disk->get($path)), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new GeoliceCaptureException('No se pudo recuperar la conexión con Geo. Guarda nuevamente tu cuenta.');
        }

        return $account;
    }

    public function saveAccount(int $userId, string $email, string $password): void
    {
        $this->locked($userId, function () use ($userId, $email, $password): void {
            if ($this->isActive($this->latest($userId))) {
                throw new GeoliceCaptureException('Espera a que termine la captura antes de cambiar la cuenta de Geo.');
            }
            $this->writeAccount($userId, ['email' => $email, 'password' => $password, 'cookies' => []]);
        });
    }

    /** @param list<array<string, mixed>> $cookies */
    public function saveCookies(int $userId, array $cookies): void
    {
        $this->locked($userId, function () use ($userId, $cookies): void {
            $account = $this->account($userId);
            if ($account !== null) {
                $this->writeAccount($userId, [...$account, 'cookies' => $cookies]);
            }
        });
    }

    public function forgetAccount(int $userId): void
    {
        $this->locked($userId, function () use ($userId): void {
            if ($this->isActive($this->latest($userId))) {
                throw new GeoliceCaptureException('Espera a que termine la captura antes de desconectar la cuenta.');
            }
            Storage::disk('courier-geo')->delete($this->folder($userId).'/cuenta.enc');
        });
    }

    public function filePath(int $userId, string $id, bool $temporary = false): string
    {
        if (! Str::isUuid($id)) {
            throw new GeoliceCaptureException('La solicitud de captura no es válida.');
        }

        return Storage::disk('courier-geo')->path($this->folder($userId).'/'.$id.'/paquetes.csv'.($temporary ? '.part' : ''));
    }

    /** @param array<string, mixed> $capture */
    public function minutes(array $capture): int
    {
        $start = CarbonImmutable::parse($capture['started_at'] ?? $capture['created_at']);
        $end = $capture['finished_at'] ? CarbonImmutable::parse($capture['finished_at']) : now();

        return max(0, (int) $start->diffInMinutes($end));
    }

    /** @return resource */
    public function lockAccount(string $email): mixed
    {
        $disk = Storage::disk('courier-geo');
        $this->ensureDirectory('bloqueos');
        $handle = fopen($disk->path('bloqueos/'.hash('sha256', Str::lower(trim($email))).'.lock'), 'c');
        if ($handle === false) {
            throw new GeoliceCaptureException('No se pudo preparar la captura. Inténtalo nuevamente.');
        }
        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new GeoliceCaptureException('Otra captura está utilizando esta cuenta de Geo. Espera su resultado antes de volver a solicitar paquetes.');
        }

        return $handle;
    }

    private function folder(int $userId): string
    {
        if ($userId <= 0) {
            throw new GeoliceCaptureException('La cuenta de Portal4N no es válida.');
        }

        return 'usuarios/'.$userId;
    }

    /** @return array<string, mixed>|null */
    private function read(string $path): ?array
    {
        $disk = Storage::disk('courier-geo');
        if (! $disk->exists($path)) {
            return null;
        }

        $data = json_decode($disk->get($path), true);

        return is_array($data) ? $data : null;
    }

    /** @param array<string, mixed> $values */
    private function write(string $path, array $values): void
    {
        $this->atomicWrite($path, json_encode($values, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed> $account */
    private function writeAccount(int $userId, array $account): void
    {
        $this->atomicWrite($this->folder($userId).'/cuenta.enc', Crypt::encryptString(json_encode($account, JSON_THROW_ON_ERROR)));
    }

    private function atomicWrite(string $path, string $contents): void
    {
        $disk = Storage::disk('courier-geo');
        $this->ensureDirectory(dirname($path));
        $temporary = $path.'.'.Str::uuid().'.tmp';
        try {
            if (! $disk->put($temporary, $contents) || ! rename($disk->path($temporary), $disk->path($path))) {
                throw new GeoliceCaptureException('No se pudo guardar el avance de la captura.');
            }
        } finally {
            $disk->delete($temporary);
        }
    }

    private function locked(int $userId, Closure $operation): mixed
    {
        $disk = Storage::disk('courier-geo');
        $folder = $this->folder($userId);
        $this->ensureDirectory($folder);
        $handle = fopen($disk->path($folder.'/solicitudes.lock'), 'c');
        if ($handle === false || ! flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new GeoliceCaptureException('No se pudo guardar la solicitud. Inténtalo nuevamente.');
        }
        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureDirectory(string $path): void
    {
        $disk = Storage::disk('courier-geo');

        // makeDirectory also chmods existing directories, which Windows mounts can reject.
        if (! $disk->directoryExists($path)) {
            $disk->makeDirectory($path);
        }
    }
}
