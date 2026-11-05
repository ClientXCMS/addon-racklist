<?php

declare(strict_types=1);

namespace App\Addons\RackList\Specs;

use App\Addons\RackList\Services\CatalogMapping;
use App\Models\Store\Product;

/**
 * Merges specs read from the product type config with the ones typed by the admin.
 * Manual values win, like host_vars over group_vars.
 */
final class SpecResolver
{
    /** @var array<string, SpecExtractorInterface> */
    private array $extractors = [];

    public function __construct(private readonly CatalogMapping $mapping) {}

    public function addExtractor(SpecExtractorInterface $extractor): void
    {
        $this->extractors[$extractor->productType()] = $extractor;
    }

    public function hasExtractor(string $productType): bool
    {
        return isset($this->extractors[$productType]);
    }

    /**
     * @return array<string, string>
     */
    public function autoSpecs(Product $product): array
    {
        $extractor = $this->extractors[(string) $product->type] ?? null;

        return $extractor === null ? [] : $extractor->extract($product);
    }

    /**
     * @return array<string, string>
     */
    public function resolve(Product $product): array
    {
        return array_merge($this->autoSpecs($product), $this->mapping->getManualSpecs($product));
    }
}
