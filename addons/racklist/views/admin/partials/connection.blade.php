<div class="card">
    <div class="card-heading">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('racklist::admin.title') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('racklist::admin.connection.help') }}</p>
        </div>
    </div>

    <form action="{{ route('admin.racklist.settings.update') }}" method="POST" class="mt-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                @include('admin/shared/input', [
                    'name' => 'api_key',
                    'type' => 'password',
                    'label' => __('racklist::admin.connection.api_key'),
                    'help' => __('racklist::admin.connection.api_key_help'),
                    'value' => '',
                    'attributes' => ['placeholder' => $maskedKey ?? 'rk_…', 'autocomplete' => 'off'],
                ])
            </div>
            <div class="pt-8 space-y-2">
                @include('admin/shared/checkbox', ['name' => 'auto_sync', 'label' => __('racklist::admin.connection.auto_sync'), 'checked' => $autoSync])
                @if ($maskedKey)
                    @include('admin/shared/checkbox', ['name' => 'remove_api_key', 'label' => __('racklist::admin.connection.remove_key'), 'checked' => false])
                @endif
            </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            {{ $maskedKey ? __('racklist::admin.connection.key_configured', ['key' => $maskedKey]) : __('racklist::admin.connection.key_missing') }}
        </p>
        <button type="submit" class="btn btn-primary mt-4">{{ __('global.save') }}</button>
    </form>

    @if ($maskedKey)
        <div class="flex flex-wrap gap-2 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <form action="{{ route('admin.racklist.test') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary"><i class="bi bi-clipboard-check"></i> {{ __('racklist::admin.actions.test') }}</button>
            </form>
            <form action="{{ route('admin.racklist.sync') }}" method="POST" onsubmit="return confirm(@js(__('racklist::admin.actions.sync_confirm')))">
                @csrf
                <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> {{ __('racklist::admin.actions.sync') }}</button>
            </form>
            <form action="{{ route('admin.racklist.reference.refresh') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> {{ __('racklist::admin.actions.refresh_reference') }}</button>
            </form>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ __('racklist::admin.actions.sync_warning') }}</p>
    @endif
</div>
