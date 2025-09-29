@extends('ptventa::layouts.master')

@push('head')
    @livewireStyles()
@endpush

@push('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('ptventa.' . getRoleRouteName(Route::currentRouteName()) . '.sale.index') }}" class="text-decoration-none">
            {{ trans('ptventa::sales.Breadcrumb_Register_1') }}
        </a>
    </li>
    <li class="breadcrumb-item active">{{ trans('ptventa::sales.Breadcrumb_Active_Register_1') }}</li>
@endpush

@section('content')
    @livewire('ptventa::sale.generate-sale')
@endsection

@include('ptventa::layouts.partials.plugins.sweetalert2')
@include('ptventa::layouts.partials.plugins.toastr')

@push('scripts')
    @livewireScripts()
    @section('sripts-generate-sale') @show
@endpush