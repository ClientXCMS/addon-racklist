@extends('admin.settings.sidebar')

@section('title', __('racklist::admin.product.title', ['name' => $product->name]))

@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('racklist::admin.product.title', ['name' => $product->name]) }}</h2>
                <a href="{{ route('admin.settings.show', ['card' => 'store', 'uuid' => 'racklist']) }}" class="text-sm text-primary">
                    <i class="bi bi-arrow-left"></i> {{ __('racklist::admin.product.back') }}
                </a>
            </div>
        </div>

        @if ($referenceError)
            <div class="mt-4 p-3 rounded-lg bg-yellow-100 text-yellow-800 text-sm">{{ __('racklist::admin.errors.reference_unavailable', ['message' => $referenceError]) }}</div>
        @endif

        <form action="{{ route('admin.racklist.products.update', $product) }}" method="POST" class="mt-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-900 dark:text-gray-400 mt-2">{{ __('racklist::admin.product.category') }}</label>
                    <select name="category" id="category" class="input-text mt-2">
                        <option value="">{{ __('racklist::admin.product.inherit', ['category' => $inherited ? ($categoryOptions[$inherited] ?? $inherited) : __('racklist::admin.groups.not_listed')]) }}</option>
                        @foreach ($categoryOptions as $id => $label)
                            <option value="{{ $id }}" @selected($override === $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pt-8">
                    @include('admin/shared/checkbox', ['name' => 'excluded', 'label' => __('racklist::admin.product.excluded'), 'checked' => $excluded])
                </div>
            </div>

            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mt-6">{{ __('racklist::admin.product.specs') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $hasExtractor ? __('racklist::admin.product.specs_auto', ['type' => $product->type]) : __('racklist::admin.product.specs_manual') }}
            </p>

            @if ($specKeys === [])
                <p class="text-sm text-gray-500 mt-2">{{ __('racklist::admin.product.specs_need_category') }}</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach ($specKeys as $key => $spec)
                        <div>
                            @include('admin/shared/input', [
                                'name' => 'specs[' . $key . ']',
                                'id' => 'spec_' . $key,
                                'label' => $spec['label'] . ($spec['unit'] ? ' (' . $spec['unit'] . ')' : ''),
                                'value' => $manualSpecs[$key] ?? '',
                                'attributes' => ['placeholder' => $autoSpecs[$key] ?? '', 'maxlength' => 500],
                            ])
                        </div>
                    @endforeach
                </div>
            @endif

            <button type="submit" class="btn btn-primary mt-6">{{ __('global.save') }}</button>
        </form>
    </div>
@endsection
