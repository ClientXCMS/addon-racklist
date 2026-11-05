<?php

declare(strict_types=1);

namespace App\Addons\RackList\Services;

use App\Addons\RackList\Jobs\SyncRackListCatalogJob;

/**
 * Collapses every catalogue change of a request into one sync.
 */
class SyncScheduler
{
    // Leaves time for an admin to finish editing prices before the catalogue is pushed.
    public const DEBOUNCE_SECONDS = 60;

    private bool $pending = false;

    public function __construct(private readonly RackListSettings $settings) {}

    public function request(): void
    {
        if ($this->pending || ! $this->settings->isAutoSyncEnabled()) {
            return;
        }
        $this->pending = true;

        // The sync driver ignores delays and runs the job inline: wait until the response is sent
        // so saving a product (and its prices) triggers a single call. Real queues dedupe through the unique lock.
        if (config('queue.default') === 'sync') {
            app()->terminating(function (): void {
                $this->pending = false;
                $this->dispatch(0);
            });

            return;
        }

        $this->pending = false;
        $this->dispatch(self::DEBOUNCE_SECONDS);
    }

    public function isPending(): bool
    {
        return $this->pending;
    }

    private function dispatch(int $delay): void
    {
        try {
            SyncRackListCatalogJob::dispatch(RackListSyncService::TRIGGER_AUTO)->delay($delay);
        } catch (\Throwable $e) {
            // Never break the admin save because the remote directory is unreachable.
            logger()->warning('RackList: unable to dispatch catalogue sync', ['error' => $e->getMessage()]);
        }
    }
}
