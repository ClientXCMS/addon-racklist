<?php

declare(strict_types=1);

namespace App\Addons\RackList\Services;

use App\Models\Store\Group;
use App\Models\Store\Product;

/**
 * Reads and writes the RackList mapping stored as group/product metadata (no migration needed).
 */
final class CatalogMapping
{
    public const META_CATEGORY = 'racklist_category';

    public const META_EXCLUDED = 'racklist_excluded';

    public const META_SPECS = 'racklist_specs';

    // RackList limits each spec value to 500 characters.
    private const SPEC_VALUE_MAX_LENGTH = 500;

    public function getGroupCategory(Group $group): ?string
    {
        return $this->normalizeCategory($group->getMetadata(self::META_CATEGORY));
    }

    /**
     * A subgroup without its own category inherits the one of its parent group.
     */
    public function resolveGroupCategory(?Group $group): ?string
    {
        if ($group === null) {
            return null;
        }

        $parent = $group->group();

        return $this->getGroupCategory($group) ?? ($parent !== null ? $this->getGroupCategory($parent) : null);
    }

    public function getProductCategoryOverride(Product $product): ?string
    {
        return $this->normalizeCategory($product->getMetadata(self::META_CATEGORY));
    }

    public function resolveProductCategory(Product $product): ?string
    {
        return $this->getProductCategoryOverride($product) ?? $this->resolveGroupCategory($product->group);
    }

    public function isExcluded(Product $product): bool
    {
        return $product->getMetadata(self::META_EXCLUDED) === '1';
    }

    /**
     * @return array<string, string>
     */
    public function getManualSpecs(Product $product): array
    {
        $decoded = json_decode((string) $product->getMetadata(self::META_SPECS), true);

        return is_array($decoded) ? $this->sanitizeSpecs($decoded) : [];
    }

    public function setGroupCategory(Group $group, ?string $category): void
    {
        $this->writeMetadata($group, self::META_CATEGORY, $this->normalizeCategory($category));
    }

    public function setProductMapping(Product $product, ?string $category, bool $excluded, array $specs): void
    {
        $specs = $this->sanitizeSpecs($specs);
        $this->writeMetadata($product, self::META_CATEGORY, $this->normalizeCategory($category));
        $this->writeMetadata($product, self::META_EXCLUDED, $excluded ? '1' : null);
        $this->writeMetadata($product, self::META_SPECS, $specs === [] ? null : json_encode($specs));
    }

    /**
     * @return array<string, string>
     */
    public function sanitizeSpecs(array $specs): array
    {
        $clean = [];
        foreach ($specs as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[a-z0-9_]{1,64}$/', $key) || ! is_scalar($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $clean[$key] = mb_substr($value, 0, self::SPEC_VALUE_MAX_LENGTH);
            }
        }

        return $clean;
    }

    private function normalizeCategory(mixed $category): ?string
    {
        if (! is_string($category)) {
            return null;
        }
        $category = trim($category);

        // RackList category ids are snake_case identifiers such as "vps_cloud".
        return preg_match('/^[a-z0-9_]{1,64}$/', $category) ? $category : null;
    }

    private function writeMetadata(Group|Product $model, string $key, ?string $value): void
    {
        if ($value === null) {
            $model->detachMetadata($key);

            return;
        }
        $model->attachMetadata($key, $value);
    }
}
