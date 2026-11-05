<div class="card mt-4">
    <div class="card-heading">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('racklist::admin.groups.title') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('racklist::admin.groups.help') }}</p>
        </div>
    </div>

    <form action="{{ route('admin.racklist.groups.update') }}" method="POST" class="mt-4">
        @csrf
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('racklist::admin.groups.group') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('racklist::admin.groups.products') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('racklist::admin.groups.category') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($groups as $group)
                        @php $current = $mapping->getGroupCategory($group); @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                                @if ($group->parent_id) <span class="text-gray-400 ml-4">↳</span> @endif
                                {{ $group->name }}
                                @if ($group->status !== 'active')
                                    <span class="text-xs text-gray-500">({{ __('racklist::admin.groups.inactive') }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $group->products_count }}</td>
                            <td class="px-4 py-3">
                                <select name="categories[{{ $group->id }}]" class="input-text" aria-label="{{ __('racklist::admin.groups.category') }} {{ $group->name }}">
                                    <option value="">{{ $group->parent_id ? __('racklist::admin.groups.inherit_parent') : __('racklist::admin.groups.not_listed') }}</option>
                                    @foreach ($categoryOptions as $id => $label)
                                        <option value="{{ $id }}" @selected($current === $id)>{{ $label }}</option>
                                    @endforeach
                                    {{-- Keep a saved value visible even when RackList is unreachable --}}
                                    @if ($current && ! isset($categoryOptions[$current]))
                                        <option value="{{ $current }}" selected>{{ $current }}</option>
                                    @endif
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-3 text-sm text-gray-500">{{ __('racklist::admin.groups.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-primary mt-4">{{ __('racklist::admin.groups.save') }}</button>
    </form>
</div>
