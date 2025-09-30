<?php

namespace Modules\PTVENTA\Http\Livewire\Sale;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\SICA\Entities\{
    Element, EPS, PensionEntity, Inventory, Movement, MovementDetail,
    MovementType, Person, PopulationGroup, WarehouseMovement, CashCount, MovementResponsibility
};
use Illuminate\Support\Facades\Gate;
use Modules\PTVENTA\Http\Controllers\PUW;

class GenerateSale extends Component
{
    public $puw;
    public $products;
    public $product_id;
    public $product_price;
    public $product_amount;
    public $product_total_amount;
    public $product_subtotal;
    public $total = 0;
    public $input_payment_value = false;
    public $payment_value;
    public $change_value;
    public Collection $selected_products;
    public $customer_document_number = 123456789;
    public $customer_document_type;
    public $customer_full_name;
    public $document_types;
    public $person_document_type;
    public $person_document_number;
    public $person_first_name;
    public $person_first_last_name;
    public $person_second_last_name;

    public function __construct()
    {
        $this->selected_products = collect();
    }

    public function mount()
    {
        $this->defaultAction();
        $this->consultCustomer();
    }

    public function render()
    {
        return view('ptventa::livewire.sale.generate-sale');
    }

    public function defaultAction()
    {
        $this->reset();
        $this->consultCustomer();
        $this->puw = PUW::getAppPuw();

        $inventories = Inventory::where('productive_unit_warehouse_id', $this->puw->id)
            ->where('amount', '>', 0)
            ->where('destination', 'Producción')
            ->where('state', 'Disponible')
            ->where(function ($query) {
                $query->whereDate('expiration_date', '>=', now())->orWhereNull('expiration_date');
            })
            ->pluck('id', 'element_id');

        $elementIds = $inventories->keys()->toArray();
        $this->products = Element::whereIn('id', $elementIds)->whereNotNull('price')->orderBy('name')->get();
        $this->document_types = getEnumValues('people', 'document_type');
    }

    public function inventoryProduct($element_id)
    {
        $inventory = Inventory::where('productive_unit_warehouse_id', $this->puw->id)
            ->where('element_id', $element_id)
            ->where('amount', '>', 0)
            ->where('destination', 'Producción')
            ->where('state', 'Disponible')
            ->where(function ($query) {
                $query->whereDate('expiration_date', '>=', now())->orWhereNull('expiration_date');
            })->select(DB::raw('SUM(amount) as product_total_amount'))
            ->first();

        $inventory->sale_price = priceFormat(Element::findOrFail($element_id)->price);
        return $inventory;
    }

    public function updatedProductId()
    {
        if (empty($this->product_id)) {
            $this->reset('product_total_amount', 'product_price', 'product_amount', 'product_subtotal');
            $this->emit('input-product-amount', 0, 0, 0, 0);
        } else {
            $inventory = $this->inventoryProduct($this->product_id);
            $product_amount_selected = $this->selected_products->where('product_element_id', $this->product_id)->sum('product_amount');
            $this->product_total_amount = $inventory->product_total_amount - $product_amount_selected;
            $this->product_price = $inventory->sale_price;
            $this->product_amount = 0; // Establecer explícitamente 0 como valor inicial
            $this->product_subtotal = null;
            $this->emit('input-product-amount', $this->product_total_amount, $this->product_price, 0, $this->total);
        }
    }

    public function updatedProductAmount($value)
    {
        if ($value < 1 || empty($this->product_id)) {
            $this->product_amount = 0; // Mantener 0 si el valor es inválido
            $this->product_subtotal = null;
        } else {
            $inventory = $this->inventoryProduct($this->product_id);
            $product_amount_selected = $this->selected_products->where('product_element_id', $this->product_id)->sum('product_amount');
            $available = $inventory->product_total_amount - $product_amount_selected;

            if ($value > $available) {
                $this->emit('message', 'alert-warning', null, 'Cantidad inválida. No puede exceder el stock disponible (' . $available . ').', null);
                $this->product_amount = $available;
            }
            $this->product_subtotal = priceFormat($this->product_amount * revertPriceFormat($this->product_price));
        }
        $this->emit('input-product-amount', $this->product_total_amount, $this->product_price, revertPriceFormat($this->product_subtotal), $this->total);
    }

    public function addProduct()
    {
        if (empty($this->product_id) || $this->product_amount < 1) {
            $this->emit('message', 'alert-warning', null, 'Seleccione un producto y especifique una cantidad válida.', null);
            return;
        }

        $inventory = $this->inventoryProduct($this->product_id);
        $product_amount_selected = $this->selected_products->where('product_element_id', $this->product_id)->sum('product_amount');
        $available = $inventory->product_total_amount - $product_amount_selected;

        if ($this->product_amount > $available) {
            $this->emit('message', 'alert-warning', null, 'Cantidad inválida. No puede exceder el stock disponible (' . $available . ').', null);
            $this->product_amount = $available;
            $this->product_subtotal = priceFormat($available * revertPriceFormat($this->product_price));
            return;
        }

        $found = false;
        foreach ($this->selected_products as $key => $product) {
            if ($product['product_element_id'] == $this->product_id) {
                $found = true;
                $updatedProduct = [
                    'product_element_id' => $product['product_element_id'],
                    'product_name' => $product['product_name'],
                    'product_amount' => $product['product_amount'] + $this->product_amount,
                    'product_price' => $product['product_price'],
                    'product_subtotal' => $product['product_subtotal'] + ($this->product_amount * revertPriceFormat($product['product_price']))
                ];
                $this->selected_products[$key] = $updatedProduct;
                break;
            }
        }

        if (!$found) {
            $this->selected_products->push([
                'product_element_id' => $this->product_id,
                'product_name' => Element::find($this->product_id)->name,
                'product_amount' => $this->product_amount,
                'product_price' => $this->product_price,
                'product_subtotal' => $this->product_amount * revertPriceFormat($this->product_price)
            ]);
        }

        $this->totalValueProducts();
        $this->reset('product_id', 'product_total_amount', 'product_price', 'product_amount', 'product_subtotal');
        $this->product_amount = 0; // Reiniciar a 0 después de agregar
        $this->emit('input-product-amount', 0, 0, 0, $this->total);
    }

    public function updatedPaymentValue($value)
    {
        $paymentVal = revertPriceFormat($value);
        $this->change_value = $paymentVal - $this->total;
        $this->emit('change_value');
    }

    public function editProduct($product_id)
    {
        foreach ($this->selected_products as $index => $product) {
            if ($product['product_element_id'] == $product_id) {
                $this->resetValues();
                $this->product_id = $product['product_element_id'];
                $inventory = $this->inventoryProduct($this->product_id);
                $this->product_price = $inventory->sale_price;
                $this->product_amount = $product['product_amount'];
                $this->product_total_amount = $inventory->product_total_amount;
                $this->product_subtotal = priceFormat($product['product_subtotal']);
                $this->selected_products->forget($index);
                $this->totalValueProducts();
                $this->emit('input-product-amount', $this->product_total_amount, $this->product_price, revertPriceFormat($this->product_subtotal), $this->total);
                break;
            }
        }
    }

    public function deleteProduct($product_id)
    {
        foreach ($this->selected_products as $index => $product) {
            if ($product['product_element_id'] == $product_id) {
                $this->selected_products->forget($index);
                $this->totalValueProducts();
                break;
            }
        }
    }

    public function totalValueProducts()
    {
        $this->total = $this->selected_products->sum('product_subtotal');
        $this->input_payment_value = $this->total > 0;
        $this->emit('input-payment-value', $this->total);
    }

    public function resetValues()
    {
        $this->reset('product_id', 'product_total_amount', 'product_price', 'product_amount', 'product_subtotal');
        $this->product_amount = 0; // Reiniciar a 0
        $this->totalValueProducts();
    }

    public function verifySelectedProduct()
    {
        if ($this->product_id && $this->product_amount >= 1) {
            $this->addProduct();
        }
        $this->change_value = $this->payment_value ? revertPriceFormat($this->payment_value) - $this->total : 0;
        $this->emit('change_value');
    }

    public function registerSale()
    {
        Gate::authorize('haveaccess', 'ptventa.admin-cashier.generate.sale');

        if ($this->selected_products->isEmpty()) {
            $this->emit('message', 'alert-warning', null, 'Debe seleccionar al menos un producto para registrar la venta.', null);
            return;
        }

        if (!Person::where('document_number', $this->customer_document_number)->exists()) {
            $this->emit('message', 'alert-warning', null, trans('ptventa::sales.Alert_Select_Client'), null);
            $this->customer_document_number = null;
            $this->customer_document_type = '----------------';
            $this->customer_full_name = '----------------';
            $this->emit('open-modal-register-customer');
            return;
        }

        try {
            DB::beginTransaction();
            $current_datetime = now()->milliseconds(0);
            $movementType = MovementType::where('name', 'Venta')->firstOrFail();

            $movement = Movement::create([
                'registration_date' => $current_datetime,
                'movement_type_id' => $movementType->id,
                'voucher_number' => 0,
                'state' => 'Aprobado',
                'price' => $this->total
            ]);

            foreach ($this->selected_products as $product) {
                $amountLeft = $product['product_amount'];
                $inventories = Inventory::where('productive_unit_warehouse_id', $this->puw->id)
                    ->where('element_id', $product['product_element_id'])
                    ->where('amount', '>', 0)
                    ->where('destination', 'Producción')
                    ->where('state', 'Disponible')
                    ->where(function ($q) {
                        $q->whereDate('expiration_date', '>=', now())->orWhereNull('expiration_date');
                    })
                    ->orderBy('expiration_date', 'asc')
                    ->get();

                foreach ($inventories as $inventory) {
                    if ($amountLeft <= 0) break;

                    $amountToSubtract = min($amountLeft, $inventory->amount);
                    $inventory->amount -= $amountToSubtract;
                    $inventory->state = ($inventory->amount > 0) ? 'Disponible' : 'No disponible';
                    $inventory->save();

                    $amountLeft -= $amountToSubtract;

                    MovementDetail::create([
                        'movement_id' => $movement->id,
                        'inventory_id' => $inventory->id,
                        'amount' => $amountToSubtract,
                        'price' => revertPriceFormat($product['product_price'])
                    ]);
                }

                if ($amountLeft > 0) {
                    throw new Exception("No hay suficiente inventario para el producto: {$product['product_name']}.");
                }
            }

            MovementResponsibility::create([
                'person_id' => Auth::user()->person_id,
                'movement_id' => $movement->id,
                'role' => 'VENDEDOR',
                'date' => $current_datetime
            ]);

            MovementResponsibility::create([
                'person_id' => Person::where('document_number', $this->customer_document_number)->first()->id,
                'movement_id' => $movement->id,
                'role' => 'CLIENTE',
                'date' => $current_datetime
            ]);

            WarehouseMovement::create([
                'productive_unit_warehouse_id' => $this->puw->id,
                'movement_id' => $movement->id,
                'role' => 'Entrega'
            ]);

            $cashCount = CashCount::where('productive_unit_warehouse_id', $this->puw->id)
                ->where('state', 'Abierta')
                ->first();

            if ($cashCount) {
                $cashCount->total_sales += $movement->price;
                $cashCount->save();
            }

            $movementType->update(['consecutive' => $movementType->consecutive + 1]);
            $movement->update(['voucher_number' => $movementType->consecutive]);

            DB::commit();

            $paymentVal = $this->payment_value ? floatval(revertPriceFormat($this->payment_value)) : 0;
            $totalVal = floatval($this->total);
            $calculatedChange = max(0, $paymentVal - $totalVal);

            $this->emit('message', 
                'success', 
                trans('ptventa::sales.Alert_Successful_Sale'), 
                trans('ptventa::sales.Alert_Change_Of') . ' ' . priceFormat($calculatedChange),
                priceFormat($calculatedChange)
            );

            $final_movement = Movement::with([
                'warehouse_movements.productive_unit_warehouse.warehouse',
                'movement_details.inventory.element.measurement_unit',
                'movement_responsibilities.person'
            ])->find($movement->id);

            $this->emit('printTicket', $final_movement);

            $this->selected_products = collect();
            $this->defaultAction();
            $this->payment_value = null;
            $this->change_value = null;
            $this->emit('clear-sale-values');

        } catch (Exception $e) {
            DB::rollBack();
            $this->emit('message', 'error', 'Operación rechazada', 'Ha ocurrido un error en el registro de la venta: ' . $e->getMessage(), null);
        }
    }

    public function consultCustomer()
    {
        $customer = Person::where('document_number', $this->customer_document_number)->first();
        if ($customer) {
            $this->customer_document_number = $customer->document_number;
            $this->customer_document_type = $customer->document_type;
            $this->customer_full_name = $customer->full_name;
        } else {
            $this->person_document_number = $this->customer_document_number;
            $this->customer_document_number = null;
            $this->customer_document_type = '----------------';
            $this->customer_full_name = '----------------';
            $this->emit('open-modal-register-customer');
        }
        $this->verifySelectedProduct();
    }

    public function registerCustomer()
    {
        $validatedData = $this->validate([
            'person_document_type' => 'required',
            'person_document_number' => 'required|digits_between:6,12|unique:people,document_number',
            'person_first_name' => 'required|min:3',
            'person_first_last_name' => 'required|min:3',
            'person_second_last_name' => 'required|min:3',
        ]);

        $person = Person::create([
            'document_type' => $validatedData['person_document_type'],
            'document_number' => $validatedData['person_document_number'],
            'first_name' => $validatedData['person_first_name'],
            'first_last_name' => $validatedData['person_first_last_name'],
            'second_last_name' => $validatedData['person_second_last_name'],
            'eps_id' => EPS::firstOrCreate(['name' => 'NO REGISTRA'])->id,
            'pension_entity_id' => PensionEntity::firstOrCreate(['name' => 'NO REGISTRA'])->id,
            'population_group_id' => PopulationGroup::firstOrCreate(['name' => 'NINGUNA'])->id
        ]);

        if ($person) {
            $this->resetFormRegisterCustomer();
            $this->emit('close-modal-register-customer');
            $this->emit('message', 'alert-success', null, trans('ptventa::sales.Alert_Registered_Client'), null);
            $this->customer_document_number = $person->document_number;
            $this->consultCustomer();
        }
    }

    public function resetFormRegisterCustomer()
    {
        $this->reset(
            'person_document_type',
            'person_document_number',
            'person_first_name',
            'person_first_last_name',
            'person_second_last_name'
        );
    }
}
