<?php

declare(strict_types=1);

namespace App\Addons\RackList\Services;

use App\Addons\RackList\Exceptions\RackListApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client for the RackList public API (https://racklist.eu/api/docs.json).
 */
class RackListClient
{
    public const BASE_URL = 'https://racklist.eu';

    // Reference data (categories, spec keys) rarely changes: one call a day is enough.
    private const REFERENCE_CACHE_TTL = 86400;

    private const TIMEOUT_SECONDS = 20;

    public function __construct(private readonly RackListSettings $settings) {}

    /**
     * Replace the whole synced catalogue. Products missing from $products are deactivated on RackList.
     */
    public function syncProducts(array $products, bool $dryRun = false, ?string $apiKey = null): array
    {
        $apiKey ??= $this->settings->getApiKey();
        if ($apiKey === null) {
            throw new RackListApiException('No RackList API key configured.', RackListApiException::MISSING_KEY);
        }

        $response = $this->send(fn () => $this->request()
            ->withToken($apiKey)
            ->withQueryParameters(['dry_run' => $dryRun ? 'true' : 'false'])
            ->post('/api/v1/sync/products', ['products' => $products]));

        return $response->json('data') ?? [];
    }

    /**
     * Category tree: families with their subcategories.
     */
    public function getCategories(): array
    {
        return Cache::remember('racklist.categories', self::REFERENCE_CACHE_TTL, function () {
            return $this->send(fn () => $this->request()->get('/api/v1/categories'))->json('data') ?? [];
        });
    }

    public function getSpecKeys(): array
    {
        return Cache::remember('racklist.spec_keys', self::REFERENCE_CACHE_TTL, function () {
            return $this->send(fn () => $this->request()->get('/api/v1/spec-keys'))->json('data') ?? [];
        });
    }

    public function forgetReferenceCache(): void
    {
        Cache::forget('racklist.categories');
        Cache::forget('racklist.spec_keys');
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->asJson()
            ->timeout(self::TIMEOUT_SECONDS);
    }

    private function send(callable $call): Response
    {
        try {
            $response = $call();
        } catch (ConnectionException $e) {
            throw new RackListApiException($e->getMessage(), RackListApiException::CONNECTION_FAILED);
        }

        if ($response->failed()) {
            throw $this->toException($response);
        }

        return $response;
    }

    private function toException(Response $response): RackListApiException
    {
        $problem = $response->json();
        $problem = is_array($problem) ? $problem : [];
        $retryAfter = $response->header('Retry-After');

        return new RackListApiException(
            (string) ($problem['detail'] ?? $problem['title'] ?? 'RackList API error'),
            (string) ($problem['code'] ?? 'HTTP_'.$response->status()),
            $response->status(),
            is_array($problem['details'] ?? null) ? $problem['details'] : [],
            is_numeric($retryAfter) ? (int) $retryAfter : null,
            isset($problem['request_id']) ? (string) $problem['request_id'] : null,
        );
    }
}
