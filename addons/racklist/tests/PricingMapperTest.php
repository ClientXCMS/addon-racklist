<?php

namespace App\Addons\RackList\Tests;

require_once __DIR__.'/RackListTestCase.php';

use App\Addons\RackList\Services\PricingMapper;
use App\Models\Store\Pricing;

class PricingMapperTest extends RackListTestCase
{
    private function pricing(array $attributes): Pricing
    {
        return (new Pricing)->forceFill($attributes + ['currency' => 'eur']);
    }

    public function test_monthly_price_with_setup_fee_and_other_cycles_as_tiers(): void
    {
        $fields = (new PricingMapper)->map($this->pricing([
            'monthly' => 9.9,
            'setup_monthly' => 5,
            'annually' => 99,
            'quarterly' => 27,
        ]));

        $this->assertSame('EUR', $fields['currency']);
        $this->assertSame('9.90', $fields['price']);
        $this->assertSame('5.00', $fields['setup_fee']);
        $this->assertSame([
            ['period' => 'quarterly', 'price' => '27.00'],
            ['period' => 'annually', 'price' => '99.00'],
        ], $fields['pricing_tiers']);
    }

    public function test_annual_only_product_has_no_monthly_price_but_keeps_its_setup_fee(): void
    {
        $fields = (new PricingMapper)->map($this->pricing(['annually' => 120, 'setup_annually' => 10]));

        $this->assertArrayNotHasKey('price', $fields);
        $this->assertSame('10.00', $fields['setup_fee']);
        $this->assertSame([['period' => 'annually', 'price' => '120.00']], $fields['pricing_tiers']);
    }

    public function test_negative_or_missing_cycles_are_not_offered(): void
    {
        $mapper = new PricingMapper;
        $pricing = $this->pricing(['monthly' => -1, 'annually' => null]);

        $this->assertFalse($mapper->hasAnyPrice($pricing));
        $this->assertSame(['currency' => 'EUR'], $mapper->map($pricing));
    }

    public function test_free_product_is_a_valid_price(): void
    {
        $this->assertSame('0.00', (new PricingMapper)->map($this->pricing(['monthly' => 0]))['price']);
    }
}
