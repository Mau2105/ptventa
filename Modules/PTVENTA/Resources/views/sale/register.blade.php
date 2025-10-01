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
    <!-- Scripts del plugin para imprimir en impresoras térmicas -->
    <script src="{{ asset('modules/ptventa/js/sale/conector_javascript_POS80C.js') }}"></script>
    <!-- Recursos para los formateadores de datos -->
    <script src="{{ asset('libs/cleave.js-1.6.0/dist/cleave.js') }}"></script>
    <!-- Formateadores de datos -->
    <script src="{{ asset('modules/ptventa/js/data-formats.js') }}"></script>
    <!-- Scripts del componente register-sale -->
    <script src="{{ asset('modules/ptventa/js/sale/register/livewire-register-sale.js') }}"></script>
    <!-- Scripts para impresión en impresora pos térmica -->
    <script src="{{ asset('modules/ptventa/js/pos_print/prints.js') }}"></script>
    <!-- Scripts para la internacionalización del alert que confirma la venta -->
    <script>
        window.translations = @json([
            'alertChangeOf' => __('ptventa::sales.Alert_Change_Of'),
            'btnAccept' => __('ptventa::sales.Btn_Accept'),
        ]);
    </script>
@endpush