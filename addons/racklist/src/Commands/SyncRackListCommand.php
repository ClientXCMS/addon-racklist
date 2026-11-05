<?php

declare(strict_types=1);

namespace App\Addons\RackList\Commands;

use App\Addons\RackList\Exceptions\RackListApiException;
use App\Addons\RackList\Services\RackListSettings;
use App\Addons\RackList\Services\RackListSyncService;
use Illuminate\Console\Command;

final class SyncRackListCommand extends Command
{
    protected $signature = 'racklist:sync {--dry-run : Ask RackList to check the catalogue without saving anything}';

    protected $description = 'Push the full ClientXCMS catalogue to RackList';

    public function handle(RackListSyncService $service, RackListSettings $settings): int
    {
        // Scheduled daily: an addon installed but not configured yet is not an error.
        if (! $settings->hasApiKey()) {
            $this->info('RackList API key not configured, nothing to sync.');

            return self::SUCCESS;
        }

        try {
            $report = $service->sync(RackListSyncService::TRIGGER_CRON, (bool) $this->option('dry-run'));
        } catch (RackListApiException $e) {
            $this->error(sprintf('[%s] %s', $e->errorCode, $e->getMessage()));

            return self::FAILURE;
        }

        $result = $report['result'] ?? [];
        $this->info(sprintf(
            'Sent %d products: %d created, %d updated, %d deactivated, %d rejected.',
            $report['sent'],
            $result['products_created'] ?? 0,
            $result['products_updated'] ?? 0,
            $result['products_deactivated'] ?? 0,
            $result['products_rejected'] ?? 0,
        ));

        return self::SUCCESS;
    }
}
