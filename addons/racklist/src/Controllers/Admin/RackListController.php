<?php

declare(strict_types=1);

namespace App\Addons\RackList\Controllers\Admin;

use App\Addons\RackList\Exceptions\RackListApiException;
use App\Addons\RackList\RackListServiceProvider;
use App\Addons\RackList\Services\CatalogBuilder;
use App\Addons\RackList\Services\CatalogMapping;
use App\Addons\RackList\Services\RackListClient;
use App\Addons\RackList\Services\RackListReference;
use App\Addons\RackList\Services\RackListSettings;
use App\Addons\RackList\Services\RackListSyncService;
use App\Http\Controllers\Controller;
use App\Models\Store\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RackListController extends Controller
{
    public function __construct(
        private readonly RackListSettings $settings,
        private readonly RackListSyncService $syncService,
    ) {}

    // Called by the core SettingsController with the request only: services are resolved here, not injected.
    public function index(Request $request): View
    {
        staff_aborts_permission(RackListServiceProvider::PERMISSION);
        $builder = app(CatalogBuilder::class);
        $mapping = app(CatalogMapping::class);
        $reference = app(RackListReference::class);

        return view('racklist_admin::index', [
            'maskedKey' => $this->settings->getMaskedApiKey(),
            'autoSync' => filter_var(setting(RackListSettings::KEY_AUTO_SYNC, true), FILTER_VALIDATE_BOOLEAN),
            'lastReport' => $this->settings->getLastReport(),
            'categoryOptions' => $reference->categoryOptions(),
            'referenceError' => $reference->error(),
            'groups' => $this->loadGroups(),
            'mapping' => $mapping,
            'catalogue' => $builder->build(),
            'currency' => $builder->currency(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        staff_aborts_permission(RackListServiceProvider::PERMISSION);
        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'regex:'.RackListSettings::API_KEY_PATTERN],
            'remove_api_key' => 'nullable',
        ], ['api_key.regex' => __('racklist::admin.errors.invalid_key_format')]);

        if ($request->boolean('remove_api_key')) {
            $this->settings->setApiKey(null);
        } elseif (! empty($validated['api_key'])) {
            $this->settings->setApiKey(trim($validated['api_key']));
        }
        $this->settings->setAutoSync($request->has('auto_sync'));

        return back()->with('success', __('racklist::admin.settings_saved'));
    }

    public function test(): RedirectResponse
    {
        staff_aborts_permission(RackListServiceProvider::PERMISSION);

        return $this->runSync(true);
    }

    public function sync(): RedirectResponse
    {
        staff_aborts_permission(RackListServiceProvider::PERMISSION);

        return $this->runSync(false);
    }

    public function refreshReference(RackListClient $client): RedirectResponse
    {
        staff_aborts_permission(RackListServiceProvider::PERMISSION);
        $client->forgetReferenceCache();

        return back()->with('success', __('racklist::admin.reference_refreshed'));
    }

    private function runSync(bool $dryRun): RedirectResponse
    {
        try {
            $report = $this->syncService->sync(RackListSyncService::TRIGGER_MANUAL, $dryRun);
        } catch (RackListApiException $e) {
            return back()->with('error', __('racklist::admin.sync_failed', ['code' => $e->errorCode, 'message' => $e->getMessage()]));
        }

        $message = __($dryRun ? 'racklist::admin.test_success' : 'racklist::admin.sync_success', [
            'sent' => $report['sent'],
            'rejected' => $report['result']['products_rejected'] ?? 0,
        ]);
        $redirect = back()->with('success', $message);

        // A dry run is never persisted: hand it to the next page view only.
        return $dryRun ? $redirect->with('racklist_dry_run', $report) : $redirect;
    }

    private function loadGroups()
    {
        // Parents first, each followed by its subgroups, to render the tree as a flat table.
        $groups = Group::query()->with('metadata')->withCount('products')->orderBy('sort_order')->get();
        $children = $groups->whereNotNull('parent_id')->groupBy('parent_id');

        return $groups->whereNull('parent_id')->flatMap(
            fn (Group $parent) => collect([$parent])->merge($children->get($parent->id, collect()))
        )->values();
    }
}
