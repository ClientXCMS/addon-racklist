<?php

namespace App\Addons\RackList\Tests;

require_once __DIR__.'/RackListTestCase.php';

use App\Addons\RackList\Services\CatalogMapping;
use App\Addons\RackList\Services\RackListSettings;
use Illuminate\Support\Facades\Http;

class RackListAdminTest extends RackListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'racklist.eu/api/v1/categories' => Http::response(['data' => [
                ['id' => 'vps', 'name' => ['en' => 'VPS', 'fr' => 'VPS'], 'subcategories' => [
                    ['id' => 'vps_cloud', 'name' => ['en' => 'Cloud VPS', 'fr' => 'Cloud VPS']],
                ]],
            ]]),
            'racklist.eu/api/v1/spec-keys' => Http::response(['data' => [
                ['id' => 'ram', 'name' => ['en' => 'RAM', 'fr' => 'RAM'], 'unit' => 'GB', 'categories' => [['id' => 'vps']]],
            ]]),
        ]);
    }

    public function test_settings_page_renders_groups_and_catalogue_preview(): void
    {
        $this->createMappedProduct();

        $this->performAdminAction('get', admin_prefix().'/settings/store/racklist')
            ->assertOk()
            ->assertSee('Cloud VPS')
            ->assertSee('Test Product');
    }

    public function test_invalid_api_key_is_rejected(): void
    {
        $this->performAdminAction('post', admin_prefix().'/racklist/settings', ['api_key' => 'not-a-key'])
            ->assertJsonValidationErrors('api_key');

        $this->assertFalse($this->app->make(RackListSettings::class)->hasApiKey());
    }

    public function test_api_key_is_saved_and_kept_when_left_empty(): void
    {
        $this->performAdminAction('post', admin_prefix().'/racklist/settings', ['api_key' => self::API_KEY, 'auto_sync' => '1'])->assertRedirect();
        $this->performAdminAction('post', admin_prefix().'/racklist/settings', ['api_key' => ''])->assertRedirect();

        $this->assertSame(self::API_KEY, $this->app->make(RackListSettings::class)->getApiKey());
    }

    public function test_group_mapping_is_saved_as_metadata(): void
    {
        $group = $this->createGroupModel();

        $this->performAdminAction('post', admin_prefix().'/racklist/groups', ['categories' => [$group->id => 'vps_cloud']])->assertRedirect();

        $this->assertSame('vps_cloud', $this->app->make(CatalogMapping::class)->getGroupCategory($group->fresh()));
    }

    public function test_product_page_lists_spec_keys_of_its_family(): void
    {
        $product = $this->createMappedProduct('vps_cloud');

        $this->performAdminAction('get', admin_prefix().'/racklist/products/'.$product->id)
            ->assertOk()
            ->assertSee('specs[ram]', false);
    }

    public function test_access_requires_permission(): void
    {
        $this->performAdminAction('post', admin_prefix().'/racklist/sync', [], ['admin.manage_products'])->assertForbidden();
    }
}
