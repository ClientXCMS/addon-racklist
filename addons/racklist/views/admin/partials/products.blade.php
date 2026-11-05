@php
    $count = count($catalogue['products']);
@endphp
<div class="card mt-4">
    <div class="card-heading">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('racklist::admin.products.title') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('racklist::admin.products.help', ['count' => $count, 'max' => \App\Addons\RackList\Services\RackListSyncService::MAX_PRODUCTS, 'currency' => $currency]) }}
            </p>
        </div>
    </div>

    <div class="overflow-x-auto mt-4">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('racklist::admin.products.product') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('racklist::admin.products.category') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('racklist::admin.products.price') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('racklist::admin.products.specs') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($catalogue['products'] as $row)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            <i class="bi bi-check-circle-fill text-green-500"></i> {{ $row['name'] }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $categoryOptions[$row['category']] ?? $row['category'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ isset($row['price']) ? $row['price'] . ' ' . $row['currency'] . ' / ' . __('racklist::admin.products.month') : count($row['pricing_tiers'] ?? []) . ' ' . __('racklist::admin.products.tiers') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                            {{ collect($row['specs'] ?? [])->map(fn ($v, $k) => $k . ': ' . $v)->implode(' · ') ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.racklist.products.edit', \App\Addons\RackList\Services\CatalogBuilder::productIdFromExternalId($row['external_id'])) }}" class="btn btn-secondary text-sm">{{ __('racklist::admin.products.configure') }}</a>
                        </td>
                    </tr>
                @endforeach
                @foreach ($catalogue['skipped'] as $skipped)
                    <tr class="opacity-70">
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            <i class="bi bi-dash-circle text-gray-400"></i> {{ $skipped['name'] }}
                        </td>
                        <td colspan="3" class="px-4 py-3 text-sm text-gray-500">{{ __('racklist::admin.products.reasons.' . $skipped['reason']) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.racklist.products.edit', $skipped['product_id']) }}" class="btn btn-secondary text-sm">{{ __('racklist::admin.products.configure') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
