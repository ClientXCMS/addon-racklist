<?php

declare(strict_types=1);

namespace App\Addons\RackList\Services;

use App\Addons\RackList\Exceptions\RackListApiException;

/**
 * Pushes the full catalogue to RackList and keeps a report of the last real sync.
 */
class RackListSyncService
{
    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_AUTO = 'auto';

    public const TRIGGER_CRON = 'cron';

    // Documented maxItems of SyncPayload.products.
    public const MAX_PRODUCTS = 100;

    public function __construct(
        private readonly CatalogBuilder $builder,
        private readonly RackListClient $client,
        private readonly RackListSettings $settings,
    ) {}

    /**
     * A dry run is checked by RackList exactly like a real sync but saves nothing, so its report is not persisted.
     *
     * @throws RackListApiException
     */
    public function sync(string $trigger, bool $dryRun = false): array
    {
        $catalogue = $this->builder->build();
        $report = [
            'at' => now()->toIso8601String(),
            'trigger' => $trigger,
            'dry_run' => $dryRun,
            'success' => false,
            'sent' => count($catalogue['products']),
            'skipped' => $catalogue['skipped'],
            'result' => null,
            'error' => null,
        ];

        try {
            $this->guardCatalogue($catalogue['products'], $dryRun);
            $report['result'] = $this->client->syncProducts($catalogue['products'], $dryRun);
            $report['success'] = true;
        } catch (RackListApiException $e) {
            $report['error'] = $this->describeError($e);
            $this->persist($report);

            throw $e;
        }

        $this->persist($report);

        return $report;
    }

    private function guardCatalogue(array $products, bool $dryRun): void
    {
        // Sending more than the limit fails, and truncating would deactivate the rest on RackList.
        if (count($products) > self::MAX_PRODUCTS) {
            throw new RackListApiException(
                __('racklist::admin.errors.too_many_products', ['count' => count($products), 'max' => self::MAX_PRODUCTS]),
                RackListApiException::TOO_MANY_PRODUCTS,
            );
        }
        // An empty real sync would deactivate every listed offer: almost always a mapping mistake.
        if ($products === [] && ! $dryRun) {
            throw new RackListApiException(__('racklist::admin.errors.empty_catalogue'), RackListApiException::EMPTY_CATALOGUE);
        }
    }

    private function describeError(RackListApiException $e): array
    {
        return [
            'code' => $e->errorCode,
            'status' => $e->status,
            'message' => $e->getMessage(),
            'details' => $e->details,
            'request_id' => $e->requestId,
        ];
    }

    private function persist(array $report): void
    {
        if (! $report['dry_run']) {
            $this->settings->storeReport($report);
        }
    }
}
