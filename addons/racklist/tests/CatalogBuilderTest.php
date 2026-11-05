<?php

namespace App\Addons\RackList\Tests;

require_once __DIR__.'/RackListTestCase.php';

use App\Addons\RackList\Services\CatalogBuilder;
use App\Addons\RackList\Services\CatalogMapping;

class CatalogBuilderTest extends RackListTestCase
{
    private function build(): array
    {
        return $this->app->make(CatalogBuilder::class)->build();
    }

    public function test_product_of_a_mapped_group_is_sent_with_its_category_and_price(): void
    {
        $product = $this->createMappedProduct('vps_cloud', ['monthly' => 12.5]);

        $catalogue = $this->build();

        $this->assertCount(1, $catalogue['products']);
        $row = $catalogue['products'][0];
        $this->assertSame('cxms_'.$product->id, $row['external_id']);
        $this->assertSame('vps_cloud', $row['category']);
        $this->assertSame('12.50', $row['price']);
        $this->assertSame('USD', $row['currency']);
        $this->assertSame('Test Product Description', $row['description']);
        $this->assertStringStartsWith('http', $row['external_url']);
    }

    public function test_unmapped_group_products_are_skipped(): void
    {
        $product = $this->createProductModel();

        $catalogue = $this->build();

        $this->assertSame([], $catalogue['products']);
        $this->assertSame(CatalogBuilder::REASON_NO_CATEGORY, $catalogue['skipped'][0]['reason']);
        $this->assertSame($product->id, $catalogue['skipped'][0]['product_id']);
    }

    public function test_subgroup_inherits_parent_category_and_product_override_wins(): void
    {
        $parent = $this->createGroupModel();
        $parent->attachMetadata('racklist_category', 'game');
        $child = $this->createGroupModel('active', $parent->id);
        $inherited = $this->createProductModel();
        $inherited->update(['group_id' => $child->id]);
        $overridden = $this->createProductModel();
        $overridden->update(['group_id' => $child->id]);
        $this->app->make(CatalogMapping::class)->setProductMapping($overridden, 'game_minecraft', false, []);

        $categories = collect($this->build()['products'])->pluck('category', 'external_id');

        $this->assertSame('game', $categories['cxms_'.$inherited->id]);
        $this->assertSame('game_minecraft', $categories['cxms_'.$overridden->id]);
    }

    public function test_excluded_product_and_product_without_price_are_skipped(): void
    {
        $excluded = $this->createMappedProduct();
        $this->app->make(CatalogMapping::class)->setProductMapping($excluded, null, true, []);
        $this->createProductModel('active', 1, []);

        $reasons = collect($this->build()['skipped'])->pluck('reason')->sort()->values()->all();

        $this->assertSame([CatalogBuilder::REASON_EXCLUDED, CatalogBuilder::REASON_NO_PRICING], $reasons);
    }

    public function test_inactive_products_are_never_sent(): void
    {
        $this->createMappedProduct();
        $this->createProductModel('hidden');

        $this->assertCount(1, $this->build()['products']);
    }

    public function test_manual_specs_are_sanitized_and_sent(): void
    {
        $product = $this->createMappedProduct();
        $this->app->make(CatalogMapping::class)->setProductMapping($product, null, false, [
            'ram' => ' 8 GB ',
            'bandwidth' => '',
            'Bad Key!' => 'dropped',
        ]);

        $this->assertSame(['ram' => '8 GB'], $this->build()['products'][0]['specs']);
    }

    public function test_external_id_round_trip(): void
    {
        $this->assertSame(42, CatalogBuilder::productIdFromExternalId('cxms_42'));
        $this->assertNull(CatalogBuilder::productIdFromExternalId('whmcs_42'));
    }
}
