<?php

namespace Modules\PTVENTA\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Modules\SICA\Entities\Inventory;
use Modules\SICA\Entities\Movement;
use Modules\SICA\Entities\MovementType;
use Modules\SICA\Entities\ProductiveUnitWarehouse as PUW;
use TCPDF;

class InventoryController extends Controller
{
    // Listado del inventario actual
    public function index()
    {
        $inventories = Inventory::with(['element']) // evitar N+1
            ->where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
            ->where('amount', '<>', 0)
            ->orderBy('updated_at', 'DESC')
            ->get();

        // AGRUPAR POR PRODUCTO (key correcto)
        $grouped = $inventories->groupBy('element_id');

        // Convertir a colección de grupos (para $loop->iteration en Blade)
        $groupedInventories = collect();
        foreach ($grouped as $grp) {
            $groupedInventories->push($grp);
        }

        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_inventory_index_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_inventory_index_title_view')
        ];

        return view('ptventa::inventory.index', compact('view', 'groupedInventories'));
    }

    public function create()
    {
        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_inventory_create_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_inventory_create_title_view')
        ];
        return view('ptventa::inventory.create', compact('view'));
    }

    public function status(Request $request)
    {
        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_inventory_status_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_inventory_status_title_view')
        ];

        $productosVencidos = Inventory::where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
            ->where('state', 'Disponible')
            ->where('expiration_date', '<', now())
            ->orderBy('expiration_date')
            ->get();

        $productosPorVencer = Inventory::where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
            ->where('state', 'Disponible')
            ->where('expiration_date', '>', now())
            ->where('expiration_date', '<=', now()->addDays(3))
            ->orderBy('expiration_date')
            ->get();

        return view('ptventa::inventory.status', compact('view', 'productosVencidos', 'productosPorVencer'));
    }

    public function low_create()
    {
        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_inventory_low_create_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_inventory_low_create_title_view')
        ];
        return view('ptventa::inventory.low', compact('view'));
    }

    public function show_entry(Movement $movement)
    {
        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_inventory_show_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_inventory_show_title_view')
        ];
        return view('ptventa::inventory.show-entry', compact('view', 'movement'));
    }

    public function showLow(Movement $movement)
    {
        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_inventory_show_low_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_inventory_show_low_title_view')
        ];
        return view('ptventa::inventory.show-low', compact('view', 'movement'));
    }

    public function reports()
    {
        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_inventory_reports_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_inventory_reports_title_view')
        ];

        return view('ptventa::reports.index', compact('view'));
    }

    // PDF: Inventario actual (fix de índices y nombres)
    public function generateInventoryPDF(Request $request)
    {
        $inventories = Inventory::with(['element'])
            ->where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
            ->where('amount', '<>', 0)
            ->orderBy('updated_at', 'DESC')
            ->get();

        $puw = PUW::getAppPuw();

        $groups = $inventories->groupBy('element_id')->values(); // colección de grupos

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $title = 'Reporte de Inventario - ' . date('Y-m-d');
        $pdf->SetTitle($title);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        $pdf->SetY(15);
        $header = 'Centro de Formación Agroindustrial "La Angostura" | Campoalegre - Huila';
        $pdf->Cell(0, 0, $header, 0, 1, 'C');

        $html = '<h4 style="text-align: center;"><strong>Bodega:</strong> ' . $puw->warehouse->name . ' - <strong>Unidad Productiva:</strong> ' . $puw->productive_unit->name . '</h4>';
        $html .= '<h3 style="text-align: center;">' . $title . '</h3>';
        $html .= '<table style="border-collapse: collapse; width: 100%;">';
        $html .= '<thead style="background-color: #f2f2f2;">';
        $html .= '<tr>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:10px;width:25px;"><b>#</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;width:130px;"><b>Producto</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:45px;"><b>N° Lote</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;width:62px;"><b>Fecha Producción</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;width:62px;"><b>Fecha Vencimiento</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:50px;"><b>Cantidad</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:50px;"><b>Precio Entrada</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:50px;"><b>Precio Venta</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:62px;"><b>Existencias</b></th>';
        $html .= '</tr></thead><tbody>';

        foreach ($groups as $idx => $group) {
            $firstRecord = $group->first();
            $rowspan = $group->count();
            $html .= '<tr>';
            $html .= '<td rowspan="'.$rowspan.'" style="border:1px solid #ddd;text-align:center;padding:8px;width:25px;">'.($idx+1).'</td>';
            $html .= '<td rowspan="'.$rowspan.'" style="border:1px solid #ddd;text-align:left;padding:8px;width:130px;">'.$firstRecord->element->name.'</td>';
            $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;width:45px;">'.$firstRecord->lot_number.'</td>';
            $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;width:62px;">'.$firstRecord->production_date.'</td>';
            $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;width:62px;">'.$firstRecord->expiration_date.'</td>';
            $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;width:50px;">'.$firstRecord->amount.'</td>';
            $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;width:50px;">'.priceFormat($firstRecord->price).'</td>';
            $html .= '<td rowspan="'.$rowspan.'" style="border:1px solid #ddd;text-align:center;padding:8px;width:50px;">'.priceFormat($firstRecord->element->price).'</td>';
            $html .= '<td rowspan="'.$rowspan.'" style="border:1px solid #ddd;text-align:center;padding:8px;width:62px;">'.$group->sum('amount').'</td>';
            $html .= '</tr>';

            foreach ($group->slice(1) as $record) {
                $html .= '<tr>';
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.$record->lot_number.'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;">'.$record->production_date.'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;">'.$record->expiration_date.'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.$record->amount.'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.priceFormat($record->price).'</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $filename = 'reporte_inventarios_'.date('Ymd').'.pdf';
        $pdf->Output($filename, 'I');
    }

    public function showInventoryEntriesForm()
    {
        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_inventory_show_entries_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_inventory_show_entries_title_view')
        ];
        $start_date = request()->input('start_date', now()->format('Y-m-d'));
        $end_date = request()->input('end_date', now()->format('Y-m-d'));

        return view('ptventa::reports.inventory-entries-form', compact('view', 'start_date', 'end_date'));
    }

    public function generateInventoryEntries(Request $request)
    {
        $startDateInput = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $endDateInput = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $startDateInput)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDateInput)->endOfDay();

        $movement_type = MovementType::where('name', 'Movimiento Interno')->firstOrFail();
        $movements = Movement::whereHas('warehouse_movements', function ($query) {
            $query->where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
                ->where('role', 'Recibe');
        })
            ->where('movement_type_id', $movement_type->id)
            ->where('state', 'Aprobado')
            ->whereBetween('registration_date', [$startDate, $endDate])
            ->orderBy('registration_date', 'ASC')
            ->get();

        return $this->showInventoryEntriesForm()->with('movements', $movements);
    }

    public function generateInventoryEntriesPDF(Request $request)
    {
        $startDateInput = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $endDateInput = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $startDateInput)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDateInput)->endOfDay();

        $movement_type = MovementType::where('name', 'Movimiento Interno')->firstOrFail();
        $movements = Movement::whereHas('warehouse_movements', function ($query) {
            $query->where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
                ->where('role', 'Recibe');
        })
            ->where('movement_type_id', $movement_type->id)
            ->where('state', 'Aprobado')
            ->whereBetween('registration_date', [$startDate, $endDate])
            ->orderBy('registration_date', 'ASC')
            ->get();

        $puw = PUW::getAppPuw();
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $title = 'Reporte de Entradas de Inventario - ' . $startDateInput . ' al ' . $endDateInput;
        $pdf->SetTitle($title);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        $pdf->SetY(15);
        $header = 'Centro de Formación Agroindustrial "La Angostura" | Campoalegre - Huila';
        $pdf->Cell(0, 0, $header, 0, 1, 'C');

        $html = '<h4 style="text-align: center;"><strong>Bodega:</strong> ' . $puw->warehouse->name . ' - <strong>Unidad Productiva:</strong> ' . $puw->productive_unit->name . '</h4>';
        $html .= '<h3 style="text-align: center;">' . $title . '</h3>';
        $html .= '<table style="border-collapse: collapse; width: 100%;">';
        $html .= '<thead style="background-color: #f2f2f2;"><tr>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:25px;"><b>#</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;width:52px;"><b>N° de Voucher</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;width:72px;"><b>Responsable que entrega</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;"><b>Fecha de ingreso</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;width:90px;"><b>Producto</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;"><b>Cantidad</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;"><b>Precio</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;"><b>Subtotal</b></th>';
        $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;"><b>Total</b></th>';
        $html .= '</tr></thead><tbody>';

        foreach ($movements as $key => $movement) {
            foreach ($movement->movement_details as $index => $movement_detail) {
                $html .= '<tr>';
                if ($index === 0) {
                    $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;width:25px;" rowspan="'.count($movement->movement_details).'">'.($key + 1).'</td>';
                    $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;width:52px;" rowspan="'.count($movement->movement_details).'">'.$movement->voucher_number.'</td>';
                    $entrega = optional($movement->movement_responsibilities->where('role','ENTREGA')->first())->person->full_name ?? '';
                    $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;width:72px;" rowspan="'.count($movement->movement_details).'">'.$entrega.'</td>';
                    $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;" rowspan="'.count($movement->movement_details).'">'.$movement->registration_date.'</td>';
                }
                $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;width:90px;">'.$movement_detail->inventory->element->name.'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.$movement_detail->amount.'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.priceFormat($movement_detail->price).'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.priceFormat($movement_detail->amount * $movement_detail->price).'</td>';
                if ($index === 0) {
                    $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;" rowspan="'.count($movement->movement_details).'">'.priceFormat($movement->price).'</td>';
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $filename = 'Reporte_entradas_inventario_'.$startDateInput.'_al_'.$endDateInput.'.pdf';
        $pdf->Output($filename, 'I');
    }

    public function showSalesForm()
    {
        $view = [
            'titlePage' => trans('ptventa::controllers.PTVENTA_sales_title_page'),
            'titleView' => trans('ptventa::controllers.PTVENTA_sales_title_view')
        ];
        $start_date = request()->input('start_date', now()->format('Y-m-d'));
        $end_date = request()->input('end_date', now()->format('Y-m-d'));

        return view('ptventa::reports.sales-form', compact('view', 'start_date', 'end_date'));
    }

public function generateSales(Request $request)
{
    $startDateInput = Carbon::parse($request->input('start_date'))->format('Y-m-d');
    $endDateInput   = Carbon::parse($request->input('end_date'))->format('Y-m-d');
    $startDate = Carbon::createFromFormat('Y-m-d', $startDateInput)->startOfDay();
    $endDate   = Carbon::createFromFormat('Y-m-d', $endDateInput)->endOfDay();

    $movement_type = MovementType::where('name', 'Venta')->firstOrFail();

    $movements = Movement::whereHas('warehouse_movements', function ($q) {
            $q->where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
              ->where('role', 'Entrega');
        })
        ->where('movement_type_id', $movement_type->id)
        ->where('state', 'Aprobado')
        ->whereBetween('registration_date', [$startDate, $endDate])
        ->orderBy('registration_date', 'ASC')
        ->get();

    // Agrupar productos por nombre + referencia
    $groupedProducts = [];
    foreach ($movements as $movement) {
        foreach ($movement->movement_details as $detail) {
            $el   = $detail->inventory->element;
            $name = $el->name ?? $el->product_name;
            $ref  = $el->reference ?? $el->reference_code ?? $el->code ?? $detail->inventory->lot_number ?? 'N/A';
            $key  = $name.'|'.$ref;

            $price = $detail->price;
            $amount = $detail->amount;
            $subtotal = $amount * $price;

            if (!isset($groupedProducts[$key])) {
                $groupedProducts[$key] = [
                    'producto'   => $name,
                    'referencia' => $ref,
                    'cantidad'   => 0,
                    'min_price'  => $price,
                    'max_price'  => $price,
                    'subtotal'   => 0,
                ];
            }
            $groupedProducts[$key]['cantidad'] += $amount;
            $groupedProducts[$key]['subtotal'] += $subtotal;
            $groupedProducts[$key]['min_price'] = min($groupedProducts[$key]['min_price'], $price);
            $groupedProducts[$key]['max_price'] = max($groupedProducts[$key]['max_price'], $price);
        }
    }

    $view = [
        'titlePage' => trans('ptventa::controllers.PTVENTA_sales_title_page'),
        'titleView' => trans('ptventa::controllers.PTVENTA_sales_title_view')
    ];

    return view('ptventa::reports.sales-form', [
        'view'          => $view,
        'start_date'    => $startDateInput,
        'end_date'      => $endDateInput,
        'movements'     => $movements,
        'groupedProducts' => array_values($groupedProducts),
    ]);
}

public function generateSalesProductsPDF(Request $request)
{
    $startDateInput = $request->input('start_date');
    $endDateInput   = $request->input('end_date');
    if (!$startDateInput || !$endDateInput) {
        return redirect()->back()->withErrors(['error' => 'Las fechas de inicio y fin son obligatorias.']);
    }

    $startDate = Carbon::parse($startDateInput)->startOfDay();
    $endDate   = Carbon::parse($endDateInput)->endOfDay();

    $movement_type = MovementType::where('name', 'Venta')->firstOrFail();
    $movements = Movement::whereHas('warehouse_movements', function ($q) {
            $q->where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
              ->where('role', 'Entrega');
        })
        ->where('movement_type_id', $movement_type->id)
        ->where('state', 'Aprobado')
        ->whereBetween('registration_date', [$startDate, $endDate])
        ->orderBy('registration_date', 'ASC')
        ->get();

    // Agrupar por nombre + referencia
    $grouped = [];
    foreach ($movements as $movement) {
        foreach ($movement->movement_details as $detail) {
            $el   = $detail->inventory->element;
            $name = $el->name ?? $el->product_name;
            $ref  = $el->reference ?? $el->reference_code ?? $el->code ?? $detail->inventory->lot_number ?? 'N/A';
            $key  = $name.'|'.$ref;

            $price = $detail->price;
            $amount = $detail->amount;
            $subtotal = $amount * $price;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'producto'   => $name,
                    'referencia' => $ref,
                    'cantidad'   => 0,
                    'min_price'  => $price,
                    'max_price'  => $price,
                    'subtotal'   => 0,
                ];
            }
            $grouped[$key]['cantidad'] += $amount;
            $grouped[$key]['subtotal'] += $subtotal;
            $grouped[$key]['min_price'] = min($grouped[$key]['min_price'], $price);
            $grouped[$key]['max_price'] = max($grouped[$key]['max_price'], $price);
        }
    }

    $puw = PUW::getAppPuw();
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $title = 'Reporte de Productos Vendidos - '.$startDateInput.' al '.$endDateInput;
    $pdf->SetTitle($title);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();
    $pdf->SetY(15);
    $pdf->Cell(0, 0, 'Centro de Formación Agroindustrial "La Angostura" | Campoalegre - Huila', 0, 1, 'C');

    $html  = '<h4 style="text-align:center;"><strong>Bodega:</strong> '.$puw->warehouse->name.' - <strong>Unidad Productiva:</strong> '.$puw->productive_unit->name.'</h4>';
    $html .= '<h3 style="text-align:center;">'.$title.'</h3>';
    $html .= '<table style="border-collapse:collapse;width:100%;">';
    $html .= '<thead style="background-color:#f2f2f2;"><tr>
        <th style="border:1px solid #ddd;text-align:center;padding:8px;width:25px;">#</th>
        <th style="border:1px solid #ddd;text-align:left;padding:8px;">Producto</th>
        <th style="border:1px solid #ddd;text-align:left;padding:8px;">Referencia</th>
        <th style="border:1px solid #ddd;text-align:center;padding:8px;width:70px;">Cantidad</th>
        <th style="border:1px solid #ddd;text-align:center;padding:8px;width:90px;">Precio</th>
        <th style="border:1px solid #ddd;text-align:center;padding:8px;width:100px;">Subtotal</th>
    </tr></thead><tbody>';

    $total = 0; $i = 0;
    foreach ($grouped as $item) {
        $i++;
        $total += $item['subtotal'];
        $priceLabel = ($item['min_price'] == $item['max_price'])
            ? priceFormat($item['min_price'])
            : priceFormat($item['min_price']).' - '.priceFormat($item['max_price']);

        $html .= "<tr>
            <td style='border:1px solid #ddd;text-align:center;padding:8px;'>{$i}</td>
            <td style='border:1px solid #ddd;text-align:left;padding:8px;'>{$item['producto']}</td>
            <td style='border:1px solid #ddd;text-align:left;padding:8px;'>{$item['referencia']}</td>
            <td style='border:1px solid #ddd;text-align:center;padding:8px;'>{$item['cantidad']}</td>
            <td style='border:1px solid #ddd;text-align:center;padding:8px;'>{$priceLabel}</td>
            <td style='border:1px solid #ddd;text-align:center;padding:8px;'>".priceFormat($item['subtotal'])."</td>
        </tr>";
    }

    $html .= "</tbody><tfoot><tr>
        <td colspan='5' style='border:1px solid #ddd;text-align:right;padding:8px;'><strong>Total General:</strong></td>
        <td style='border:1px solid #ddd;text-align:center;padding:8px;'><strong>".priceFormat($total)."</strong></td>
    </tr></tfoot></table>";

    $pdf->writeHTML($html, true, false, true, false, '');
    $filename = 'Reporte_productos_vendidos_'.$startDateInput.'_al_'.$endDateInput.'.pdf';
    $pdf->Output($filename, 'I');
}



public function generateSalesPDF(Request $request)
{
    $startDateInput = Carbon::parse($request->input('start_date'))->format('Y-m-d');
    $endDateInput   = Carbon::parse($request->input('end_date'))->format('Y-m-d');
    $startDate = Carbon::createFromFormat('Y-m-d', $startDateInput)->startOfDay();
    $endDate   = Carbon::createFromFormat('Y-m-d', $endDateInput)->endOfDay();

    $movement_type = MovementType::where('name', 'Venta')->firstOrFail();
    $movements = Movement::whereHas('warehouse_movements', function ($query) {
            $query->where('productive_unit_warehouse_id', PUW::getAppPuw()->id)
                  ->where('role', 'Entrega');
        })
        ->where('movement_type_id', $movement_type->id)
        ->where('state', 'Aprobado')
        ->whereBetween('registration_date', [$startDate, $endDate])
        ->orderBy('registration_date', 'ASC')
        ->get();

    $puw = PUW::getAppPuw();
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $title = 'Reporte de Ventas - ' . $startDateInput . ' al ' . $endDateInput;
    $pdf->SetTitle($title);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();
    $pdf->SetY(15);
    $pdf->Cell(0, 0, 'Centro de Formación Agroindustrial "La Angostura" | Campoalegre - Huila', 0, 1, 'C');

    $html  = '<h4 style="text-align:center;"><strong>Bodega:</strong> '.$puw->warehouse->name.' - <strong>Unidad Productiva:</strong> '.$puw->productive_unit->name.'</h4>';
    $html .= '<h3 style="text-align:center;">'.$title.'</h3>';
    $html .= '<table style="border-collapse:collapse;width:100%;">';
    $html .= '<thead style="background-color:#f2f2f2;"><tr>';
    $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:25px;"><b>#</b></th>';
    $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:60px;"><b>N° Comprobante</b></th>';
    $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;width:120px;"><b>Cliente</b></th>';
    $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;width:90px;"><b>Fecha</b></th>';
    $html .= '<th style="border:1px solid #ddd;text-align:left;padding:8px;"><b>Producto</b></th>';
    $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:60px;"><b>Cantidad</b></th>';
    $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:70px;"><b>Precio</b></th>';
    $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:80px;"><b>Subtotal</b></th>';
    $html .= '<th style="border:1px solid #ddd;text-align:center;padding:8px;width:80px;"><b>Total</b></th>';
    $html .= '</tr></thead><tbody>';

    $granTotal = 0;

    foreach ($movements as $key => $movement) {
        $detalles = $movement->movement_details;
        $rowspan  = count($detalles);
        $cliente  = optional($movement->movement_responsibilities->where('role','CLIENTE')->first())->person->full_name ?? 'N/A';

        foreach ($detalles as $index => $detail) {
            $html .= '<tr>';
            if ($index === 0) {
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;" rowspan="'.$rowspan.'">'.($key + 1).'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;" rowspan="'.$rowspan.'">'.$movement->voucher_number.'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;" rowspan="'.$rowspan.'">'.$cliente.'</td>';
                $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;" rowspan="'.$rowspan.'">'.$movement->registration_date.'</td>';
            }
            $subtotal = $detail->amount * $detail->price;
            $html .= '<td style="border:1px solid #ddd;text-align:left;padding:8px;">'.$detail->inventory->element->name.'</td>';
            $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.$detail->amount.'</td>';
            $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.priceFormat($detail->price).'</td>';
            $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;">'.priceFormat($subtotal).'</td>';

            if ($index === 0) {
                $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;" rowspan="'.$rowspan.'">'.priceFormat($movement->price).'</td>';
            }
            $html .= '</tr>';
        }
        $granTotal += $movement->price;
    }

    $html .= '</tbody><tfoot><tr>';
    $html .= '<td colspan="8" style="border:1px solid #ddd;text-align:right;padding:8px;"><strong>Total General:</strong></td>';
    $html .= '<td style="border:1px solid #ddd;text-align:center;padding:8px;"><strong>'.priceFormat($granTotal).'</strong></td>';
    $html .= '</tr></tfoot></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $filename = 'Reporte_ventas_'.$startDateInput.'_al_'.$endDateInput.'.pdf';
    $pdf->Output($filename, 'I');
}

}