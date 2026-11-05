<?php

declare(strict_types=1);

namespace App\Addons\RackList\Services;

use App\Addons\RackList\Exceptions\RackListApiException;

/**
 * RackList reference data (categories, spec keys) shaped for the admin forms.
 * Failures are kept instead of thrown so the page still renders when RackList is down.
 */
final class RackListReference
{
    private ?string $error = null;

    public function __construct(private readonly RackListClient $client) {}

    /**
     * Flat [id => label] list, subcategories indented under their family.
     *
     * @return array<string, string>
     */
    public function categoryOptions(): array
    {
        $options = [];
        foreach ($this->categories() as $family) {
            $options[(string) $family['id']] = $this->label($family);
            foreach ($family['subcategories'] ?? [] as $subcategory) {
                $options[(string) $subcategory['id']] = '— '.$this->label($subcategory);
            }
        }

        return $options;
    }

    /**
     * Family id of a category ("vps_cloud" -> "vps"), used to filter spec keys.
     */
    public function familyOf(?string $categoryId): ?string
    {
        foreach ($this->categories() as $family) {
            if ($family['id'] === $categoryId) {
                return $family['id'];
            }
            foreach ($family['subcategories'] ?? [] as $subcategory) {
                if ($subcategory['id'] === $categoryId) {
                    return $family['id'];
                }
            }
        }

        return null;
    }

    /**
     * Spec keys accepted by a family, as [id => ['label' => ..., 'unit' => ...]].
     *
     * @return array<string, array{label: string, unit: ?string}>
     */
    public function specKeysFor(?string $family): array
    {
        if ($family === null) {
            return [];
        }
        $keys = [];
        foreach ($this->fetch(fn () => $this->client->getSpecKeys()) as $specKey) {
            $families = array_column($specKey['categories'] ?? [], 'id');
            if (in_array($family, $families, true)) {
                $keys[(string) $specKey['id']] = ['label' => $this->label($specKey), 'unit' => $specKey['unit'] ?? null];
            }
        }

        return $keys;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    private function categories(): array
    {
        return $this->fetch(fn () => $this->client->getCategories());
    }

    private function fetch(callable $call): array
    {
        try {
            $data = $call();
        } catch (RackListApiException $e) {
            $this->error = $e->getMessage();

            return [];
        }

        return is_array($data) ? array_filter($data, 'is_array') : [];
    }

    private function label(array $item): string
    {
        $locale = str_starts_with(app()->getLocale(), 'fr') ? 'fr' : 'en';

        return (string) ($item['name'][$locale] ?? $item['name']['en'] ?? $item['id']);
    }
}
