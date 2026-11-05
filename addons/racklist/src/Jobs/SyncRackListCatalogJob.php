<?php

declare(strict_types=1);

namespace App\Addons\RackList\Jobs;

use App\Addons\RackList\Exceptions\RackListApiException;
use App\Addons\RackList\Services\RackListSettings;
use App\Addons\RackList\Services\RackListSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Unique until processing: changes made while a sync runs queue a fresh one,
 * while a burst of edits before it starts collapses into a single call.
 */
final class SyncRackListCatalogJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $uniqueFor = 600;

    public function __construct(public readonly string $trigger = RackListSyncService::TRIGGER_AUTO) {}

    public function handle(RackListSyncService $service, RackListSettings $settings): void
    {
        if (! $settings->hasApiKey()) {
            return;
        }

        try {
            $service->sync($this->trigger);
        } catch (RackListApiException $e) {
            $this->handleFailure($e);
        }
    }

    private function handleFailure(RackListApiException $e): void
    {
        // The 429 answer tells exactly when the sliding window frees a slot.
        if ($e->isRateLimited() && $this->job !== null) {
            $this->release($e->retryAfter ?? 60);

            return;
        }

        // Other errors (bad key, validation, connection) are kept in the report shown in the admin.
        logger()->warning('RackList sync failed', ['code' => $e->errorCode, 'message' => $e->getMessage()]);
    }
}
