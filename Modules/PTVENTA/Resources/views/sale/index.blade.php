@extends('ptventa::layouts.master')

@push('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('ptventa.' . getRoleRouteName(Route::currentRouteName()) . '.sale.index') }}" class="text-decoration-none">
            {{ trans('ptventa::sales.Breadcrumb_Sales_1') }}
        </a>
    </li>
    <li class="breadcrumb-item active">{{ trans('ptventa::sales.Breadcrumb_Active_Sales_1') }}</li>
@endpush

@section('content')
    <div class="card card-success card-outline shadow-sm">
        <div class="card-body pt-0">
            <div class="text-end my-2">
                @if (Auth::user()->havePermission('ptventa.' . getRoleRouteName(Route::currentRouteName()) . '.sale.register'))
                    <a href="{{ route('ptventa.' . getRoleRouteName(Route::currentRouteName()) . '.sale.register') }}"
                        class="btn btn-sm btn-success">
                        <i class="fa-solid fa-plus"></i>
                        {{ trans('ptventa::sales.Btn_Register_Sale') }}
                    </a>
                @endif
            </div>

            @if ($cashCount)
                @if ($sales->count())
                    <div class="table-responsive">
                        <table class="table table-hover" id="sales-table">
<thead class="table-dark">
<tr>
    <th class="text-center">#</th>
    <th class="text-center">{{ trans('ptventa::sales.1T_Voucher') }}</th>
    <th>{{ trans('ptventa::sales.1T_Client') }}</th>
    <th class="text-center">{{ trans('ptventa::sales.1T_Date') }}</th>
    <th class="text-center">{{ trans('ptventa::sales.1T_Products') }}</th>
    <th class="text-center">{{ trans('ptventa::sales.1T_State') }}</th>
    <th class="text-center">{{ trans('ptventa::sales.1T_Value') }}</th>
    <th class="text-center">Acción</th> {{-- NUEVA --}}
</tr>
</thead>
<tbody>
@foreach ($sales as $s)
<tr>
    <td class="text-center">{{ $loop->iteration }}</td>
    <td class="text-center">{{ $s->voucher_number }}</td>
    <td>{{ $s->movement_responsibilities->where('role', 'CLIENTE')->first()->person->full_name ?? 'N/A' }}</td>
    <td class="text-center">{{ $s->registration_date }}</td>
    <td>
        <ul class="mb-0 ps-3">
            @foreach ($s->movement_details as $detail)
                <li>
                    {{ $detail->inventory->element->product_name }}
                    <strong> (x{{ $detail->amount }})</strong>
                </li>
            @endforeach
        </ul>
    </td>
    <td class="text-center">
        <span class="badge bg-{{ $s->state == 'Aprobado' ? 'success' : 'warning' }}">
            {{ $s->state }}
        </span>
    </td>
    <td class="text-center fw-bold">{{ priceFormat($s->price) }}</td>
    <td class="text-center">
        {{-- Usa los nombres de ruta que ya tienes definidos en routes --}}
        <a href="{{ route('ptventa.' . getRoleRouteName(Route::currentRouteName()) . '.movements.sale.show', $s->id) }}"
           class="btn btn-sm btn-primary">
            Ver
        </a>
    </td>
</tr>
@endforeach
</tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">{{ trans('ptventa::sales.1T_Total') }}</td>
                                    <td class="text-center fw-bold text-success">{{ priceFormat($sales->sum('price')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center text-danger">
                        <strong>{{ trans('ptventa::sales.Text_Optional_1') }}</strong>
                    </div>
                @endif
            @else
                <div class="text-center text-danger">
                    <strong>{{ trans('ptventa::sales.Text_Optional_2') }}</strong>
                </div>
            @endif
        </div>
    </div>
@endsection
