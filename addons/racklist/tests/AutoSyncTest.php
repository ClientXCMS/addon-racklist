<?php

namespace App\Addons\RackList\Tests;

require_once __DIR__.'/RackListTestCase.php';

use App\Addons\RackList\Jobs\SyncRackListCatalogJob;
use App\Addons\RackList\Services\SyncScheduler;
use Illuminate\Support\Facades\Queue;

class AutoSyncTest extends RackListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // A real queue driver dispatches right away (delayed); the sync driver waits for terminate.
        config(['queue.default' => 'database']);
    }

    public function test_price_change_requests_a_delayed_sync(): void
    {
        $product = $this->createMappedProduct();
        $this->configureApiKey();
        Queue::fake();

        $product->pricing()->first()->update(['monthly' => 20]);

        Queue::assertPushed(SyncRackListCatalogJob::class, fn ($job) => $job->delay === SyncScheduler::DEBOUNCE_SECONDS);
    }

    public function test_stock_change_does_not_request_a_sync(): void
    {
        $product = $this->createMappedProduct();
        $this->configureApiKey();
        Queue::fake();

        $product->update(['stock' => 0]);

        Queue::assertNothingPushed();
    }

    public function test_name_change_requests_a_sync(): void
    {
        $product = $this->createMappedProduct();
        $this->configureApiKey();
        Queue::fake();

        $product->update(['name' => 'Renamed']);

        Queue::assertPushed(SyncRackListCatalogJob::class);
    }

    public function test_nothing_is_dispatched_without_api_key_or_when_disabled(): void
    {
        Queue::fake();
        $product = $this->createMappedProduct();
        $product->update(['name' => 'No key']);

        $this->configureApiKey();
        $this->app->make(\App\Addons\RackList\Services\RackListSettings::class)->setAutoSync(false);
        $product->update(['name' => 'Disabled']);

        Queue::assertNothingPushed();
    }

    public function test_sync_driver_collapses_a_request_into_one_job_on_terminate(): void
    {
        config(['queue.default' => 'sync']);
        $product = $this->createMappedProduct();
        $this->configureApiKey();
        Queue::fake();

        $product->update(['name' => 'First']);
        $product->pricing()->first()->update(['monthly' => 30]);
        Queue::assertNothingPushed();

        $this->app->terminate();

        Queue::assertPushed(SyncRackListCatalogJob::class, 1);
    }
}
