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

    // Agrupar solo por nombre de producto (eliminada referencia)
    $groupedProducts = [];
    foreach ($movements as $movement) {
        foreach ($movement->movement_details as $detail) {
            $el   = $detail->inventory->element;
            $name = $el->name ?? $el->product_name ?? 'N/A'; // Fallback seguro
            $key  = $name;

            $price = $detail->price;
            $amount = $detail->amount;
            $subtotal = $amount * $price;

            if (!isset($groupedProducts[$key])) {
                $groupedProducts[$key] = [
                    'producto'   => $name,
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
    $endDateInput = $request->input('end_date');

    if (!$startDateInput || !$endDateInput) {
        return redirect()->back()->withErrors(['error' => 'Las fechas de inicio y fin son obligatorias.']);
    }

    $startDate = Carbon::parse($startDateInput)->startOfDay();
    $endDate = Carbon::parse($endDateInput)->endOfDay();

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

    // Agrupación de productos (tu lógica está perfecta)
    $grouped = [];
    foreach ($movements as $movement) {
        foreach ($movement->movement_details as $detail) {
            $el = $detail->inventory->element;
            $name = $el->name ?? $el->product_name ?? 'Sin nombre';
            $key = $name;
            $price = $detail->price ?? 0;
            $amount = $detail->amount ?? 0;
            $subtotal = $amount * $price;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'producto' => $name,
                    'cantidad' => 0,
                    'min_price' => $price,
                    'max_price' => $price,
                    'subtotal' => 0,
                ];
            }
            $grouped[$key]['cantidad'] += $amount;
            $grouped[$key]['subtotal'] += $subtotal;
            $grouped[$key]['min_price'] = min($grouped[$key]['min_price'], $price);
            $grouped[$key]['max_price'] = max($grouped[$key]['max_price'], $price);
        }
    }

    ksort($grouped); // Orden alfabético

    $puw = PUW::getAppPuw();

    // ========================
    // CONFIGURACIÓN DEL PDF
    // ========================
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Márgenes más equilibrados
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(15);

    // Título del documento
    $pdf->SetTitle('Reporte de Productos Vendidos - ' . $startDateInput . ' al ' . $endDateInput);

    // Fuente principal
    $pdf->SetFont('helvetica', '', 11);
    $pdf->AddPage();

    // ========================
    // ENCABEZADO BONITO Y CENTRADO
    // ========================
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'CENTRO DE FORMACIÓN AGROINDUSTRIAL "LA ANGOSTURA"', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 8, 'Campoalegre - Huila', 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'REPORTE DE PRODUCTOS VENDIDOS', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 8, 'Período: del ' . \Carbon\Carbon::parse($startDateInput)->format('d/m/Y') . ' al ' . \Carbon\Carbon::parse($endDateInput)->format('d/m/Y'), 0, 1, 'C');
    $pdf->Ln(3);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, 'Bodega: ' . $puw->warehouse->name . ' | Unidad Productiva: ' . $puw->productive_unit->name, 0, 1, 'C');
    
    $pdf->Ln(8);

    // ========================
    // TABLA CON ESTILO PROFESIONAL Y LÍNEAS FUERTES
    // ========================
    $html = '<style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        th {
            background-color: #333;
            color: white;
            padding: 12px 8px;
            text-align: center;
            border: 2px solid #000;
        }
        td {
            padding: 10px 8px;
            border: 1.5px solid #000;
            text-align: center;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .total-row {
            background-color: #e6e6e6;
            font-weight: bold;
            font-size: 11pt;
        }
    </style>';

    $html .= '<table>
        <thead>
            <tr>
                <th width="6%">#</th>
                <th width="44%" class="text-left">PRODUCTO</th>
                <th width="15%">CANTIDAD</th>
                <th width="15%">PRECIO</th>
                <th width="20%">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($grouped)) {
        $html .= '<tr><td colspan="5" style="padding:20px; font-style:italic;">No se encontraron ventas en el rango de fechas seleccionado.</td></tr>';
    } else {
        $i = 1;
        $totalGeneral = 0;

        foreach ($grouped as $item) {
            $totalGeneral += $item['subtotal'];
            $precioTexto = ($item['min_price'] == $item['max_price'])
                ? priceFormat($item['min_price'])
                : priceFormat($item['min_price']) . ' - ' . priceFormat($item['max_price']);

            $cantidad = number_format($item['cantidad'], 0, ',', '.');

            $html .= "<tr>
                <td>{$i}</td>
                <td class=\"text-left\">{$item['producto']}</td>
                <td>{$cantidad}</td>
                <td>{$precioTexto}</td>
                <td>" . priceFormat($item['subtotal']) . "</td>
            </tr>";
            $i++;
        }

        // Fila del total general
        $html .= '<tr class="total-row">
            <td colspan="4" style="text-align:right; padding-right:15px;">TOTAL GENERAL:</td>
            <td>' . priceFormat($totalGeneral) . '</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    // ========================
    // PIE DE PÁGINA
    // ========================
    $html .= '<br><br>';
    $html .= '<table width="100%">
        <tr>
            <td width="50%" style="border-top:1px solid #000; padding-top:20px; text-align:center;">
                <br><br>__________________________<br>
                Responsable del Reporte
            </td>
            <td width="50%" style="border-top:1px solid #000; padding-top:20px; text-align:center;">
                <br><br>__________________________<br>
                Revisado por
            </td>
        </tr>
    </table>';

    $html .= '<div style="text-align:center; font-size:9pt; margin-top:20px; color:#555;">
        Reporte generado el ' . \Carbon\Carbon::now()->format('d/m/Y H:i') . 
        ' | Sistema de Punto de Venta - La Angostura
    </div>';

    // Escribir todo el HTML
    $pdf->writeHTML($html, true, false, true, false, '');

    // Nombre del archivo
    $filename = 'Reporte_Productos_Vendidos_' . $startDateInput . '_al_' . $endDateInput . '.pdf';
    
    // Salida
    return $pdf->Output($filename, 'I');
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