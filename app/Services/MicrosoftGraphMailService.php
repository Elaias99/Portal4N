<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MicrosoftGraphMailService
{
    private function tenantId(): string
    {
        return (string) config('services.microsoft.tenant_id');
    }

    public function hasValidSessionToken(): bool
    {
        $token = session('outlook_access_token');
        $expiresAt = session('outlook_token_expires_at');

        return filled($token) && filled($expiresAt) && now()->timestamp < (int) $expiresAt;
    }

    public function getAuthorizationUrl(): string
    {
        $state = Str::random(40);

        session(['outlook_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => route('outlook-mails.callback'),
            'response_mode' => 'query',
            'scope' => 'offline_access User.Read Mail.Read',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return "https://login.microsoftonline.com/{$this->tenantId()}/oauth2/v2.0/authorize?{$query}";
    }

    public function getSignedInUser(): array
    {
        $token = session('outlook_access_token');

        $response = Http::withToken($token)
            ->get('https://graph.microsoft.com/v1.0/me', [
                '$select' => 'displayName,mail,userPrincipalName',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('No se pudo obtener el usuario autenticado.');
        }

        return $response->json();
    }

    public function exchangeCodeForToken(string $code): array
    {
        $expectedState = session('outlook_oauth_state');
        $receivedState = request()->query('state');

        if (filled($expectedState) && $expectedState !== $receivedState) {
            throw new \RuntimeException('El parámetro state de OAuth no coincide.');
        }

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$this->tenantId()}/oauth2/v2.0/token",
            [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('outlook-mails.callback'),
                'scope' => 'offline_access User.Read Mail.Read',
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('No se pudo obtener el token de Microsoft.');
        }

        return $response->json();
    }

    public function listLatamCandidateEmails(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $token = session('outlook_access_token');

        $query = [
            '$top' => 100,
            '$select' => 'id,subject,from,receivedDateTime,hasAttachments',
            '$orderby' => 'receivedDateTime desc',
        ];

        $filters = [];

        if (filled($fechaDesde)) {
            $filters[] = 'receivedDateTime ge ' . $this->formatGraphDateStart($fechaDesde);
        }

        if (filled($fechaHasta)) {
            $filters[] = 'receivedDateTime lt ' . $this->formatGraphDateEndExclusive($fechaHasta);
        }

        if (!empty($filters)) {
            $query['$filter'] = implode(' and ', $filters);
        }

        $items = [];
        $nextUrl = 'https://graph.microsoft.com/v1.0/me/messages';

        do {
            $response = $nextUrl === 'https://graph.microsoft.com/v1.0/me/messages'
                ? Http::withToken($token)->get($nextUrl, $query)
                : Http::withToken($token)->get($nextUrl);

            if (!$response->successful()) {
                throw new \RuntimeException('No se pudieron consultar los correos.');
            }

            $items = array_merge($items, $response->json('value', []));
            $nextUrl = $response->json('@odata.nextLink');
        } while ($nextUrl);

        $filtered = [];

        foreach ($items as $item) {
            $hasAttachments = (bool) data_get($item, 'hasAttachments', false);
            $from = mb_strtolower((string) data_get($item, 'from.emailAddress.address', ''));
            $receivedAt = (string) data_get($item, 'receivedDateTime', '');

            if (!$hasAttachments) {
                continue;
            }

            if ($from !== 'noreply2.croamis@latam.com') {
                continue;
            }

            if (!$this->isWithinDateRange($receivedAt, $fechaDesde, $fechaHasta)) {
                continue;
            }

            $filtered[] = [
                'id' => (string) data_get($item, 'id', ''),
                'from' => (string) data_get($item, 'from.emailAddress.address', ''),
                'subject' => (string) data_get($item, 'subject', ''),
                'received_at' => data_get($item, 'receivedDateTime'),
            ];
        }

        return $filtered;
    }

    public function downloadFirstPdfAttachment(string $messageId): array
    {
        $token = session('outlook_access_token');

        $encodedMessageId = rawurlencode($messageId);

        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/me/messages/{$encodedMessageId}/attachments");

        if (!$response->successful()) {
            throw new \RuntimeException('No se pudieron consultar los adjuntos.');
        }

        $attachments = $response->json('value', []);

        foreach ($attachments as $attachment) {
            $attachmentId = (string) ($attachment['id'] ?? '');
            $name = (string) ($attachment['name'] ?? '');
            $contentType = (string) ($attachment['contentType'] ?? '');
            $odataType = (string) ($attachment['@odata.type'] ?? '');

            $isPdf = str_ends_with(mb_strtolower($name), '.pdf')
                || mb_strtolower($contentType) === 'application/pdf';

            $isFileAttachment = $odataType === '#microsoft.graph.fileAttachment' || $odataType === '';

            if (!$isPdf || !$isFileAttachment) {
                continue;
            }

            $contentBytes = $attachment['contentBytes'] ?? null;

            if (is_string($contentBytes) && $contentBytes !== '') {
                $decoded = base64_decode($contentBytes, true);

                if ($decoded !== false) {
                    return [
                        'name' => $name,
                        'content' => $decoded,
                    ];
                }
            }

            if ($attachmentId !== '') {
                $encodedAttachmentId = rawurlencode($attachmentId);

                $rawResponse = Http::withToken($token)
                    ->get("https://graph.microsoft.com/v1.0/me/messages/{$encodedMessageId}/attachments/{$encodedAttachmentId}/\$value");

                if ($rawResponse->successful()) {
                    return [
                        'name' => $name,
                        'content' => $rawResponse->body(),
                    ];
                }
            }
        }

        throw new \RuntimeException('El correo no contiene un PDF válido.');
    }
    private function formatGraphDateStart(string $date): string
    {
        return Carbon::parse($date)
            ->startOfDay()
            ->utc()
            ->format('Y-m-d\TH:i:s\Z');
    }

    private function formatGraphDateEndExclusive(string $date): string
    {
        return Carbon::parse($date)
            ->addDay()
            ->startOfDay()
            ->utc()
            ->format('Y-m-d\TH:i:s\Z');
    }

    private function isWithinDateRange(string $receivedAt, ?string $fechaDesde, ?string $fechaHasta): bool
    {
        if ($receivedAt === '') {
            return false;
        }

        $received = Carbon::parse($receivedAt);

        if (filled($fechaDesde) && $received->lt(Carbon::parse($fechaDesde)->startOfDay())) {
            return false;
        }

        if (filled($fechaHasta) && $received->gte(Carbon::parse($fechaHasta)->addDay()->startOfDay())) {
            return false;
        }

        return true;
    }
}