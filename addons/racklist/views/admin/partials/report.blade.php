@php
    $result = $report['result'] ?? [];
    $notices = array_merge(
        array_map(fn ($n) => $n + ['type' => 'rejected'], $result['rejected'] ?? []),
        array_map(fn ($n) => $n + ['type' => 'ignored'], $result['ignored'] ?? []),
    );
@endphp
<div class="card mt-4">
    <div class="card-heading">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $title }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ \Carbon\Carbon::parse($report['at'])->format('d/m/Y H:i') }}
                · {{ __('racklist::admin.report.triggers.' . ($report['trigger'] ?? 'manual')) }}
                · {{ trans_choice('racklist::admin.report.sent', $report['sent'] ?? 0, ['count' => $report['sent'] ?? 0]) }}
            </p>
        </div>
        <span class="inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium text-white {{ $report['success'] ? 'bg-green-500' : 'bg-red-600' }}">
            {{ $report['success'] ? __('racklist::admin.report.success') : __('racklist::admin.report.failed') }}
        </span>
    </div>

    @if (! empty($report['error']))
        <div class="mt-3 p-3 rounded-lg bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300 text-sm">
            <strong>{{ $report['error']['code'] }}</strong> — {{ $report['error']['message'] }}
            @foreach ($report['error']['details'] ?? [] as $detail)
                <div class="mt-1">#{{ $detail['index'] ?? '?' }} · {{ $detail['field'] ?? '' }} : {{ $detail['message'] ?? '' }}</div>
            @endforeach
            @if (! empty($report['error']['request_id']))
                <div class="mt-1 text-xs">Request ID : {{ $report['error']['request_id'] }}</div>
            @endif
        </div>
    @endif

    @if ($result)
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4">
            @foreach (['products_created', 'products_updated', 'products_deactivated', 'products_rejected', 'conflicts_detected'] as $counter)
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800">
                    <div class="text-xs text-gray-500">{{ __('racklist::admin.report.counters.' . $counter) }}</div>
                    <div class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ (int) ($result[$counter] ?? 0) }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($notices)
        <ul class="mt-4 text-sm space-y-1">
            @foreach ($notices as $notice)
                <li class="{{ $notice['type'] === 'rejected' ? 'text-red-600' : 'text-yellow-700 dark:text-yellow-400' }}">
                    <i class="bi {{ $notice['type'] === 'rejected' ? 'bi-x-circle' : 'bi-exclamation-triangle' }}"></i>
                    {{ $notice['external_id'] ?? '—' }} · {{ $notice['field'] ?? '' }} : {{ $notice['message'] ?? '' }}
                </li>
            @endforeach
        </ul>
    @endif
</div>
