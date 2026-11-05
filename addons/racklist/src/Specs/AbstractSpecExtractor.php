<?php

declare(strict_types=1);

namespace App\Addons\RackList\Specs;

use App\Models\Store\Product;
use Illuminate\Database\Eloquent\Model;
use Throwable;

abstract class AbstractSpecExtractor implements SpecExtractorInterface
{
    /**
     * Eloquent config model of the module, keyed by product_id.
     *
     * @return class-string<Model>
     */
    abstract protected function configModel(): string;

    /**
     * @return array<string, string>
     */
    abstract protected function mapConfig(Model $config): array;

    public function extract(Product $product): array
    {
        $modelClass = $this->configModel();
        // The module may be disabled or not installed: no specs rather than a failed sync.
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $config = $modelClass::query()->where('product_id', $product->id)->first();
        } catch (Throwable $e) {
            logger()->warning('RackList: unable to read product config', ['product' => $product->id, 'error' => $e->getMessage()]);

            return [];
        }

        return $config === null ? [] : array_filter($this->mapConfig($config), fn ($value) => $value !== null);
    }

    protected function formatQuantity(mixed $value, string $unit): ?string
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            return null;
        }

        // "4.0" -> "4", "0.50" -> "0.5"
        $number = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return $number.' '.$unit;
    }
}
