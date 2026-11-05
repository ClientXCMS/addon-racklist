<?php

declare(strict_types=1);

namespace App\Addons\RackList\Specs;

use App\Models\Store\Product;

/**
 * Reads RackList specs (key => string value such as "8 GB") from a product type configuration.
 */
interface SpecExtractorInterface
{
    /**
     * Product type uuid handled by this extractor (Product::$type).
     */
    public function productType(): string;

    /**
     * @return array<string, string>
     */
    public function extract(Product $product): array;
}
