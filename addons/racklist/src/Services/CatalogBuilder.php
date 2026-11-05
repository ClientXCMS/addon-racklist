<?php

declare(strict_types=1);

namespace App\Addons\RackList\Services;

use App\Addons\RackList\Specs\SpecResolver;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the full catalogue sent to POST /api/v1/sync/products.
 */
final class CatalogBuilder
{
    // Prefix must never change: RackList matches existing offers on external_id.
    public const EXTERNAL_ID_PREFIX = 'cxms_';

    public const REASON_EXCLUDED = 'excluded';

    public const REASON_NO_CATEGORY = 'no_category';

    public const REASON_NO_PRICING = 'no_pricing';

    public const REASON_INACTIVE_GROUP = 'inactive_group';

    private const MAX_FEATURES = 100;

    public function __construct(
        private readonly CatalogMapping $mapping,
        private readonly SpecResolver $specs,
        private readonly PricingMapper $pricingMapper,
    ) {}

    /**
     * @return array{products: array<int, array>, skipped: array<int, array{product_id: int, name: string, reason: string}>}
     */
    public function build(): array
    {
        $currency = $this->currency();
        $pricings = $this->loadPricings($currency);
        $products = [];
        $skipped = [];

        foreach ($this->loadProducts() as $product) {
            $reason = $this->skipReason($product, $pricings->get($product->id));
            if ($reason !== null) {
                $skipped[] = ['product_id' => $product->id, 'name' => (string) $product->name, 'reason' => $reason];

                continue;
            }
            $products[] = $this->buildProduct($product, $pricings->get($product->id));
        }

        return ['products' => $products, 'skipped' => $skipped];
    }

    public function buildProduct(Product $product, Pricing $pricing): array
    {
        $payload = [
            'external_id' => self::EXTERNAL_ID_PREFIX.$product->id,
            'name' => Str::limit((string) $product->name, 150, ''),
            'category' => $this->mapping->resolveProductCategory($product),
            ...$this->pricingMapper->map($pricing),
            'external_url' => $product->group?->route(),
        ];

        $description = $this->plainDescription($product);
        if ($description !== '') {
            $payload['description'] = $description;
        }

        $features = $this->features($product);
        if ($features !== []) {
            $payload['features'] = $features;
        }

        $specs = $this->specs->resolve($product);
        if ($specs !== []) {
            $payload['specs'] = $specs;
        }

        return array_filter($payload, fn ($value) => $value !== null);
    }

    public function currency(): string
    {
        return strtoupper((string) setting('store_currency', 'EUR'));
    }

    public static function productIdFromExternalId(string $externalId): ?int
    {
        return str_starts_with($externalId, self::EXTERNAL_ID_PREFIX)
            ? (int) substr($externalId, strlen(self::EXTERNAL_ID_PREFIX))
            : null;
    }

    private function skipReason(Product $product, ?Pricing $pricing): ?string
    {
        return match (true) {
            $this->mapping->isExcluded($product) => self::REASON_EXCLUDED,
            $product->group === null || $product->group->status !== 'active' => self::REASON_INACTIVE_GROUP,
            $this->mapping->resolveProductCategory($product) === null => self::REASON_NO_CATEGORY,
            $pricing === null || ! $this->pricingMapper->hasAnyPrice($pricing) => self::REASON_NO_PRICING,
            default => null,
        };
    }

    /**
     * @return Collection<int, Product>
     */
    private function loadProducts(): Collection
    {
        return Product::query()
            ->where('status', 'active')
            ->with(['group.metadata', 'metadata'])
            ->orderBy('group_id')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, Pricing> keyed by product id
     */
    private function loadPricings(string $currency): Collection
    {
        return Pricing::query()
            ->where('related_type', 'product')
            ->where('currency', $currency)
            ->get()
            ->keyBy('related_id');
    }

    private function plainDescription(Product $product): string
    {
        $text = html_entity_decode(strip_tags((string) $product->description), ENT_QUOTES | ENT_HTML5);

        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $text)), 0, 65535);
    }

    /**
     * @return array<int, string>
     */
    private function features(Product $product): array
    {
        $lines = array_map(fn (string $line) => mb_substr(strip_tags($line), 0, 255), $product->getMetadataLines());

        return array_slice(array_values(array_filter($lines, fn (string $line) => trim($line) !== '')), 0, self::MAX_FEATURES);
    }
}
