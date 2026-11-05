<?php

declare(strict_types=1);

namespace App\Addons\RackList;

use App\Addons\RackList\Commands\SyncRackListCommand;
use App\Addons\RackList\Controllers\Admin\RackListController;
use App\Addons\RackList\Observers\CatalogChangeObserver;
use App\Addons\RackList\Services\CatalogMapping;
use App\Addons\RackList\Services\RackListSettings;
use App\Addons\RackList\Services\SyncScheduler;
use App\Addons\RackList\Specs\ProxmoxSpecExtractor;
use App\Addons\RackList\Specs\PterodactylSpecExtractor;
use App\Addons\RackList\Specs\SpecResolver;
use App\Extensions\BaseAddonServiceProvider;
use App\Models\Store\Group;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use Illuminate\Console\Scheduling\Schedule;

class RackListServiceProvider extends BaseAddonServiceProvider
{
    public const PERMISSION = 'admin.racklist.manage';

    protected string $uuid = 'racklist';

    public function register(): void
    {
        $this->app->singleton(RackListSettings::class);
        $this->app->singleton(CatalogMapping::class);
        // Singleton so every observer call of a request shares the same "sync already requested" flag.
        $this->app->singleton(SyncScheduler::class);
        $this->app->singleton(SpecResolver::class, function ($app) {
            $resolver = new SpecResolver($app->make(CatalogMapping::class));
            $resolver->addExtractor(new ProxmoxSpecExtractor);
            $resolver->addExtractor(new PterodactylSpecExtractor);

            return $resolver;
        });
    }

    public function boot(): void
    {
        $this->loadViews();
        $this->loadTranslations();
        $this->registerRoutes();
        $this->registerSettings();
        $this->registerObservers();

        if ($this->app->runningInConsole()) {
            $this->commands([SyncRackListCommand::class]);
        }

        // Catches what observers cannot see (module config edits, metadata overlay, failed calls).
        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)->command('racklist:sync')->dailyAt('04:00');
        });
    }

    private function registerObservers(): void
    {
        Product::observe(CatalogChangeObserver::class);
        Group::observe(CatalogChangeObserver::class);
        Pricing::observe(CatalogChangeObserver::class);
    }

    private function registerRoutes(): void
    {
        \Route::middleware(['web', 'admin'])
            ->prefix(admin_prefix('racklist'))
            ->name('admin.racklist.')
            ->group(function () {
                require addon_path('racklist', 'routes/admin.php');
            });
    }

    private function registerSettings(): void
    {
        $this->app->make('settings')->addCardItem(
            'store',
            'racklist',
            'racklist::admin.title',
            'racklist::admin.description',
            'bi bi-hdd-rack',
            [RackListController::class, 'index'],
            self::PERMISSION,
        );
    }
}
