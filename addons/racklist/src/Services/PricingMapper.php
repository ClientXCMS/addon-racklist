<?php

declare(strict_types=1);

namespace App\Addons\RackList\Services;

use App\Models\Store\Pricing;

/**
 * Converts a ClientXCMS Pricing row into RackList price fields.
 * RackList takes one monthly "price"; every other billing cycle becomes a pricing tier.
 */
final class PricingMapper
{
    private const TIER_CYCLES = ['weekly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially', 'onetime'];

    // Documented upper bound of every RackList amount.
    private const MAX_AMOUNT = 99999999.99;

    /**
     * @return array{price?: string, currency: string, setup_fee?: string, pricing_tiers?: array<int, array{period: string, price: string}>}
     */
    public function map(Pricing $pricing): array
    {
        $fields = ['currency' => strtoupper((string) $pricing->currency)];

        $monthly = $this->amount($pricing->getAttribute('monthly'));
        if ($monthly !== null) {
            $fields['price'] = $monthly;
        }

        $setupFee = $this->setupFee($pricing, $monthly !== null);
        if ($setupFee !== null) {
            $fields['setup_fee'] = $setupFee;
        }

        $tiers = $this->tiers($pricing);
        if ($tiers !== []) {
            $fields['pricing_tiers'] = $tiers;
        }

        return $fields;
    }

    public function hasAnyPrice(Pricing $pricing): bool
    {
        return $this->amount($pricing->getAttribute('monthly')) !== null || $this->tiers($pricing) !== [];
    }

    private function setupFee(Pricing $pricing, bool $hasMonthly): ?string
    {
        if ($hasMonthly) {
            return $this->amount($pricing->getAttribute('setup_monthly'));
        }
        // Without a monthly price, the setup fee of the first offered cycle is the closest match.
        foreach (self::TIER_CYCLES as $cycle) {
            if ($this->amount($pricing->getAttribute($cycle)) !== null) {
                return $this->amount($pricing->getAttribute('setup_'.$cycle));
            }
        }

        return null;
    }

    /**
     * @return array<int, array{period: string, price: string}>
     */
    private function tiers(Pricing $pricing): array
    {
        $tiers = [];
        foreach (self::TIER_CYCLES as $cycle) {
            $price = $this->amount($pricing->getAttribute($cycle));
            if ($price !== null) {
                $tiers[] = ['period' => $cycle, 'price' => $price];
            }
        }

        return $tiers;
    }

    private function amount(mixed $value): ?string
    {
        // null or a negative value means the cycle is not offered.
        if (! is_numeric($value) || (float) $value < 0 || (float) $value > self::MAX_AMOUNT) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
