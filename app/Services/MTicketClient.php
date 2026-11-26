<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MTicketClient
{
    public function api(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('kanguro.mticket.api_base'), '/'))
            ->timeout((int) config('kanguro.mticket.timeout', 20))
            ->acceptJson();
    }

    public function clientApi(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('kanguro.mticket.client_base'), '/'))
            ->timeout((int) config('kanguro.mticket.timeout', 20))
            ->acceptJson();
    }

    public function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) config('kanguro.mticket.storage_base'), '/').'/'.ltrim($path, '/');
    }

    public function downloadLinks(): array
    {
        return [
            'android' => (string) config('kanguro.mticket.play_store_url'),
            'ios' => (string) config('kanguro.mticket.app_store_url'),
            'site' => (string) config('kanguro.mticket.site_url'),
            'package' => (string) config('kanguro.mticket.android_package'),
        ];
    }

    public function eventDeepLink(string|int $idOrSlug): string
    {
        return rtrim((string) config('kanguro.mticket.site_url'), '/').'/eventos/'.$idOrSlug;
    }

    public function get(string $path, array $query = []): array
    {
        $response = $this->api()->get($path, $query);
        if (! $response->successful()) {
            Log::warning('mTicket API error', ['path' => $path, 'status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Não foi possível contactar o mTicket.', $response->status() >= 400 ? $response->status() : 502);
        }

        return $response->json() ?? [];
    }

    public function getClient(string $path, array $query = []): array
    {
        $response = $this->clientApi()->get($path, $query);
        if (! $response->successful()) {
            Log::warning('mTicket client API error', ['path' => $path, 'status' => $response->status()]);
            throw new \RuntimeException('Não foi possível contactar o mTicket.', $response->status() >= 400 ? $response->status() : 502);
        }

        return $response->json() ?? [];
    }
}
