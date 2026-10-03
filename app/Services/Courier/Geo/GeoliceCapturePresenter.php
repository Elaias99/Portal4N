<?php

namespace App\Services\Courier\Geo;

use Carbon\CarbonImmutable;

class GeoliceCapturePresenter
{
    public function __construct(private readonly GeoliceCaptureStore $store)
    {
    }

    /** @return array<string, mixed> */
    public function forUser(int $userId, string $suggestedMonth): array
    {
        $accountError = null;

        try {
            $account = $this->store->account($userId);
        } catch (GeoliceCaptureException $exception) {
            $account = null;
            $accountError = $exception->getMessage();
        }

        $capture = $this->store->latest($userId);

        return [
            'account_email' => $account['email'] ?? null,
            'account_error' => $accountError,
            'capture' => $capture ? $this->present($capture) : null,
            'active' => $this->store->isActive($capture),
            'from' => $capture['from'] ?? now()->startOfMonth()->toDateString(),
            'to' => $capture['to'] ?? now()->toDateString(),
            'payment_period' => $capture
                ? $this->month($capture['payment_period'])
                : $suggestedMonth,
        ];
    }

    /** @param array<string, mixed> $capture
     *  @return array<string, mixed>
     */
    public function present(array $capture): array
    {
        $ready = $capture['state'] === 'listo'
            && is_file($this->store->filePath($capture['user_id'], $capture['id']));

        return [
            'id' => $capture['id'],
            'from_label' => CarbonImmutable::parse($capture['from'])->format('d-m-Y'),
            'to_label' => CarbonImmutable::parse($capture['to'])->format('d-m-Y'),
            'payment_period' => $this->month($capture['payment_period']),
            'state' => $capture['state'],
            'active' => $this->store->isActive($capture),
            'message' => $capture['message'],
            'minutes' => $this->store->minutes($capture),
            'summary' => $capture['summary'],
            'finished_label' => $capture['finished_at']
                ? CarbonImmutable::parse($capture['finished_at'])->format('d-m-Y H:i')
                : null,
            'download_url' => $ready ? route('courier.geo.download', $capture['id']) : null,
            'review_url' => $ready ? route('courier.geo.review', $capture['id']) : null,
            'status_url' => route('courier.geo.status', $capture['id']),
        ];
    }

    private function month(string $period): string
    {
        return substr($period, 0, 4).'-'.substr($period, 4, 2);
    }
}
