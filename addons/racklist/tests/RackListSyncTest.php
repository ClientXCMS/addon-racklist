<?php

namespace App\Addons\RackList\Tests;

require_once __DIR__.'/RackListTestCase.php';

use App\Addons\RackList\Exceptions\RackListApiException;
use App\Addons\RackList\Services\RackListSettings;
use App\Addons\RackList\Services\RackListSyncService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class RackListSyncTest extends RackListTestCase
{
    private function syncService(): RackListSyncService
    {
        return $this->app->make(RackListSyncService::class);
    }

    private function fakeSuccess(): void
    {
        Http::fake(['racklist.eu/api/v1/sync/products*' => Http::response([
            'status' => 'success',
            'data' => ['products_synced' => 1, 'products_created' => 1, 'products_rejected' => 0, 'rejected' => [], 'ignored' => []],
        ])]);
    }

    public function test_sync_sends_full_catalogue_with_bearer_key_and_stores_report(): void
    {
        $this->configureApiKey();
        $product = $this->createMappedProduct();
        $this->fakeSuccess();

        $report = $this->syncService()->sync(RackListSyncService::TRIGGER_MANUAL);

        $this->assertTrue($report['success']);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer '.self::API_KEY)
            && str_contains($request->url(), 'dry_run=false')
            && $request['products'][0]['external_id'] === 'cxms_'.$product->id);
        $this->assertSame(1, $this->app->make(RackListSettings::class)->getLastReport()['result']['products_created']);
    }

    public function test_dry_run_is_flagged_and_not_persisted(): void
    {
        $this->configureApiKey();
        $this->createMappedProduct();
        $this->fakeSuccess();

        $this->syncService()->sync(RackListSyncService::TRIGGER_MANUAL, true);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'dry_run=true'));
        $this->assertNull($this->app->make(RackListSettings::class)->getLastReport());
    }

    public function test_empty_catalogue_is_refused_before_calling_racklist(): void
    {
        $this->configureApiKey();
        Http::fake();

        try {
            $this->syncService()->sync(RackListSyncService::TRIGGER_MANUAL);
            $this->fail('An empty sync must be refused.');
        } catch (RackListApiException $e) {
            $this->assertSame(RackListApiException::EMPTY_CATALOGUE, $e->errorCode);
        }
        Http::assertNothingSent();
        $this->assertFalse($this->app->make(RackListSettings::class)->getLastReport()['success']);
    }

    public function test_missing_key_raises_a_clear_error(): void
    {
        $this->createMappedProduct();
        Http::fake();

        try {
            $this->syncService()->sync(RackListSyncService::TRIGGER_MANUAL);
            $this->fail('A sync without key must be refused.');
        } catch (RackListApiException $e) {
            $this->assertSame(RackListApiException::MISSING_KEY, $e->errorCode);
        }
        Http::assertNothingSent();
    }

    public function test_validation_problem_is_exposed_with_its_details(): void
    {
        $this->configureApiKey();
        $this->createMappedProduct();
        Http::fake(['racklist.eu/*' => Http::response([
            'title' => 'Validation failed', 'status' => 422, 'code' => 'VALIDATION_FAILED', 'detail' => 'Invalid payload',
            'details' => [['index' => 0, 'field' => 'category', 'message' => 'Unknown category']],
        ], 422)]);

        try {
            $this->syncService()->sync(RackListSyncService::TRIGGER_MANUAL);
            $this->fail('A 422 must raise an exception.');
        } catch (RackListApiException $e) {
            $this->assertSame('VALIDATION_FAILED', $e->errorCode);
            $this->assertSame('category', $e->details[0]['field']);
        }
    }

    public function test_rate_limit_exposes_retry_after(): void
    {
        $this->configureApiKey();
        $this->createMappedProduct();
        Http::fake(['racklist.eu/*' => Http::response(['code' => 'TOO_MANY_REQUESTS'], 429, ['Retry-After' => '42'])]);

        try {
            $this->syncService()->sync(RackListSyncService::TRIGGER_AUTO);
            $this->fail('A 429 must raise an exception.');
        } catch (RackListApiException $e) {
            $this->assertTrue($e->isRateLimited());
            $this->assertSame(42, $e->retryAfter);
        }
    }

    public function test_api_key_is_stored_encrypted(): void
    {
        $this->configureApiKey();

        $this->assertNotSame(self::API_KEY, setting(RackListSettings::KEY_API_KEY));
        $this->assertSame(self::API_KEY, $this->app->make(RackListSettings::class)->getApiKey());
    }
}
