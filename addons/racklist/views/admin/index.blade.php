@extends('admin.settings.sidebar')

@section('title', __('racklist::admin.title'))

@section('setting')
    @if ($referenceError)
        <div class="alert text-yellow-800 bg-yellow-100 mb-4 p-4 rounded-lg" role="alert">
            {{ __('racklist::admin.errors.reference_unavailable', ['message' => $referenceError]) }}
        </div>
    @endif

    @include('racklist_admin::partials.connection')

    @if (session('racklist_dry_run'))
        @include('racklist_admin::partials.report', ['report' => session('racklist_dry_run'), 'title' => __('racklist::admin.report.dry_run_title')])
    @endif

    @if ($lastReport)
        @include('racklist_admin::partials.report', ['report' => $lastReport, 'title' => __('racklist::admin.report.title')])
    @endif

    @include('racklist_admin::partials.groups')

    @include('racklist_admin::partials.products')
@endsection
