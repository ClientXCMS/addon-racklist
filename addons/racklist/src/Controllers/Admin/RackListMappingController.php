<?php

declare(strict_types=1);

namespace App\Addons\RackList\Controllers\Admin;

use App\Addons\RackList\RackListServiceProvider;
use App\Addons\RackList\Services\CatalogMapping;
use App\Addons\RackList\Services\RackListReference;
use App\Addons\RackList\Services\SyncScheduler;
use App\Addons\RackList\Specs\SpecResolver;
use App\Http\Controllers\Controller;
use App\Models\Store\Group;
use App\Models\Store\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RackListMappingController extends Controller
{
    public function __construct(
        private readonly CatalogMapping $mapping,
        private readonly SyncScheduler $scheduler,
    ) {}

    public function updateGroups(Request $request): RedirectResponse
    {
        staff_aborts_permission(RackListServiceProvider::PERMISSION);
        $validated = $request->validate([
            'categories' => 'array',
            'categories.*' => ['nullable', 'string', 'regex:/^[a-z0-9_]{1,64}$/'],
        ]);

        $groups = Group::query()->whereIn('id', array_keys($validated['categories'] ?? []))->get();
        foreach ($groups as $group) {
            $this->mapping->setGroupCategory($group, $validated['categories'][$group->id] ?? null);
        }
        // Metadata writes are not seen by the model observers.
        $this->scheduler->request();

        return back()->with('success', __('racklist::admin.mapping_saved'));
    }

    public function editProduct(Product $product, RackListReference $reference, SpecResolver $specs): View
    {
        staff_aborts_permission(RackListServiceProvider::PERMISSION);
        $this->shareSettingsSidebar();
        $category = $this->mapping->resolveProductCategory($product);

        return view('racklist_admin::product', [
            'product' => $product,
            'categoryOptions' => $reference->categoryOptions(),
            'referenceError' => $reference->error(),
            'override' => $this->mapping->getProductCategoryOverride($product),
            'inherited' => $this->mapping->resolveGroupCategory($product->group),
            'excluded' => $this->mapping->isExcluded($product),
            'specKeys' => $reference->specKeysFor($reference->familyOf($category)),
            'autoSpecs' => $specs->autoSpecs($product),
            'manualSpecs' => $this->mapping->getManualSpecs($product),
            'hasExtractor' => $specs->hasExtractor((string) $product->type),
        ]);
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        staff_aborts_permission(RackListServiceProvider::PERMISSION);
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'regex:/^[a-z0-9_]{1,64}$/'],
            'excluded' => 'nullable',
            'specs' => 'array',
            'specs.*' => 'nullable|string|max:500',
        ]);

        $this->mapping->setProductMapping(
            $product,
            $validated['category'] ?? null,
            $request->boolean('excluded'),
            $validated['specs'] ?? [],
        );
        $this->scheduler->request();

        return back()->with('success', __('racklist::admin.mapping_saved'));
    }

    private function shareSettingsSidebar(): void
    {
        $card = app('settings')->getCards()->firstWhere('uuid', 'store');
        abort_if($card === null, 404);
        \View::share('current_card', $card);
        \View::share('current_item', $card->items->firstWhere('uuid', 'racklist'));
    }
}
