<?php

namespace App\Addons\RackList\Tests;

use App\Addons\RackList\RackListServiceProvider;
use App\Addons\RackList\Services\RackListSettings;
use App\Models\Store\Group;
use App\Models\Store\Product;
use Composer\Autoload\ClassLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Extensions are never enabled in the testing environment, so the addon
 * namespace and provider (routes, views, translations) are registered here by hand.
 *
 * vendor/bin/phpunit addons/racklist/tests
 */
abstract class RackListTestCase extends TestCase
{
    use RefreshDatabase;

    protected const API_KEY = 'rk_0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->addPsr4('App\\Addons\\RackList\\', dirname(__DIR__).'/src');
        }
        // The core store card is only registered for real HTTP requests.
        if (app('settings')->getCurrentCard('store') === null) {
            app('settings')->addCard('store', 'admin.settings.store.title', 'admin.settings.store.description', 2);
        }
        $this->app->register(RackListServiceProvider::class);
        // Routes added after boot are not in the name index yet.
        $this->app['router']->getRoutes()->refreshNameLookups();

        // Test helpers create USD prices.
        setting()->set('store_currency', 'USD');
    }

    protected function configureApiKey(): void
    {
        $this->app->make(RackListSettings::class)->setApiKey(self::API_KEY);
    }

    protected function createMappedProduct(string $category = 'vps_cloud', array $prices = ['monthly' => 10]): Product
    {
        $group = Group::first() ?? $this->createGroupModel();
        $group->attachMetadata('racklist_category', $category);

        return $this->createProductModel('active', 1, $prices);
    }
}
