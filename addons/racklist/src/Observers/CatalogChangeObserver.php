<?php

declare(strict_types=1);

namespace App\Addons\RackList\Observers;

use App\Addons\RackList\Services\SyncScheduler;
use App\Models\Store\Group;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use Illuminate\Database\Eloquent\Model;

/**
 * Requests a RackList sync when a change visible on the directory is saved.
 */
final class CatalogChangeObserver
{
    // Stock moves on every order and sort/pin only affect the local store: they must not trigger a sync.
    private const RELEVANT_ATTRIBUTES = [
        Product::class => ['name', 'status', 'description', 'group_id', 'type'],
        Group::class => ['name', 'status', 'slug', 'parent_id'],
    ];

    public function __construct(private readonly SyncScheduler $scheduler) {}

    public function created(Model $model): void
    {
        if ($this->concernsProduct($model)) {
            $this->scheduler->request();
        }
    }

    public function updated(Model $model): void
    {
        if ($this->isRelevantChange($model)) {
            $this->scheduler->request();
        }
    }

    public function deleted(Model $model): void
    {
        if ($this->concernsProduct($model)) {
            $this->scheduler->request();
        }
    }

    public function restored(Model $model): void
    {
        $this->deleted($model);
    }

    private function isRelevantChange(Model $model): bool
    {
        if (! $this->concernsProduct($model)) {
            return false;
        }
        $relevant = self::RELEVANT_ATTRIBUTES[$model::class] ?? null;

        // Every Pricing column is a price, a setup fee or the currency.
        return $relevant === null ? $model->wasChanged() : $model->wasChanged($relevant);
    }

    private function concernsProduct(Model $model): bool
    {
        return ! ($model instanceof Pricing) || $model->related_type === 'product';
    }
}
