$(document).ready(function() {
    $("#payment_value").click(function() {
        $(this).select(); // Seleccionar todo el contenido del input cuando se de un click dentro del campo de valor de pago
    });
});

// Calcular el valor de cambio de acuerdo al total de la compra y el valor de pago de la compra
function calculate(total) {
    var payment_value = revertPriceFormat($("#payment_value").val());
    $("#change_value").removeClass('text-success').addClass('text-danger').val(priceFormat(payment_value - total));
    $('#sale_button').prop('disabled', true); // Desactivar botón de realizar venta
    if (total != 0 && payment_value >= total) {
        $("#change_value").removeClass('text-danger').addClass('text-success').val(priceFormat(payment_value - total));
        $("#sale_button").prop('disabled', false); // Activar botón de realizar venta
    }
    if (total > 0) { // Verificar que el total sea mayor a 0 para así activar o desactivar el botón de realizar venta
        $("#payment_value").prop('disabled', false); // Activar el input del valor de pago
    } else {
        $("#payment_value").prop('disabled', true); // Desactivar el input del valor de pago
    }
    input_payment_value(total);
}

// Configuración para calcular el valor de cambio de una venta y activación/desactivación del botón guardar venta
function input_payment_value(total) {
    var $payment_value = $('#payment_value'); // Instanciar el elemento de valor de cambio
    var $change_value = $('#change_value'); // Instanciar el elemento de valor de cambio
    var $sale_button = $('#sale_button'); // Instanciar botón de realizar venta
    $payment_value.off('input').on('input', function() {
        var input_payment_value = $(this);
        var val_payment_value = revertPriceFormat(input_payment_value.val());
        if (isNaN(val_payment_value) || val_payment_value < 0) {
            input_payment_value.val(0);
            $change_value.removeClass('text-success').addClass('text-danger').val(priceFormat(0));
            $sale_button.prop('disabled', true); // Desactivar botón de realizar venta
        } else if (val_payment_value >= total) {
            $change_value.removeClass('text-danger').addClass('text-success').val(priceFormat(val_payment_value - total));
            $sale_button.prop('disabled', false); // Activar botón de realizar venta
        } else {
            $change_value.removeClass('text-success').addClass('text-danger').val(priceFormat(val_payment_value - total));
            $sale_button.prop('disabled', true); // Desactivar botón de realizar venta
        }
    });
    $('#total').val(priceFormat(total));
    $payment_value.trigger('input');
    if (total == 0) {
        $sale_button.prop('disabled', true); // Desactivar botón de realizar venta
    }
}

// Limpiar valores de venta
Livewire.on('clear-sale-values', function() {
    $("#product_total_amount").val('');
    $("#product_price").val(priceFormat(0));
    $("#product_amount").val(1);
    $("#product_subtotal").val(priceFormat(0));
    $("#total").val(priceFormat(0));
    $("#payment_value").val(priceFormat(0));
    $("#change_value").val(priceFormat(0));
});

// Lanzar mensajes
Livewire.on('message', function(type, action, message, change_value) {
    const color = {
        success: 'green',
        error: 'red'
    };
    if (type == 'alert-success') {
        Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 2000
        });
    } else if (type == 'alert-warning') {
        Swal.fire({
            position: 'top-end',
            icon: 'warning',
            title: message,
            showConfirmButton: false,
            timer: 1500
        });
    } else {
Swal.fire({
    title: action ?? '¡Venta realizada!',
    html: (type == 'success')
        ? '<div class="py-2">' +
              '<h4 class="text-secondary">Debe devolver al cliente:</h4>' +
              '<h1 class="text-success">' + change_value + '</h1>' +
          '</div>'
        : '<p>' + message + '</p>',
    icon: type,
    iconColor: color[type],
    confirmButtonText: window.translations.btnAccept,
    confirmButtonColor: 'green'
});

    }
});

// Calcular valor de cambio
Livewire.on('change_value', function() {
    $('#payment_value').trigger('input');
});

// Llamado de función para calcular el valor de cambio de una venta y activación/desactivación del botón guardar venta
Livewire.on('input-payment-value', function(total) {
    input_payment_value(total);
});

// Establecer configuraciones para el campo de ingreso de cantidad
Livewire.on('input-product-amount', function(product_total_amount, product_price, product_subtotal, total) {
    var $product_amount = $('#product_amount'); // Instanciar el campo de cantidad de producto
    var $product_subtotal = $('#product_subtotal'); // Instancia el campo subtotal del producto
    var $total = $('#total'); // Instanciar el campo total de la venta
    var $product_price = $('#product_price'); // Instanciar el campo de precio del producto
    product_price = revertPriceFormat(product_price); // Convertir el precio formateado a número

    // Establecer valores iniciales
    $product_price.val(priceFormat(product_price));
    $product_amount.val(1);
    $product_subtotal.val(priceFormat(product_subtotal));
    $total.val(priceFormat(total));
    $("#product_total_amount").val(product_total_amount);

    calculate(total);

    if (product_total_amount == 0) {
        $product_amount.prop('disabled', true); // Desactivar el campo Cantidad
    } else {
        $product_amount.prop('disabled', false).focus(); // Activar campo Cantidad
        $product_amount.off('input').on('input', function() { // Establecer propiedades para el campo de cantidad, subtotal de productos y total de la venta
            var input = $(this);
            var val = parseInt(input.val());
            if (isNaN(val) || val < 1) {
                input.val(1);
                $product_subtotal.val(priceFormat(product_price));
                $total.val(priceFormat(total));
            } else if (val > product_total_amount) {
                input.val(product_total_amount);
                $product_subtotal.val(priceFormat(product_total_amount * product_price));
                $total.val(priceFormat(product_total_amount * product_price));
            } else {
                input.val(val);
                $product_subtotal.val(priceFormat(val * product_price));
                $total.val(priceFormat(val * product_price));
            }
            calculate($total.val());
        });
    }
});

// Definir constante para manipular los distintos eventos manejados por el modal de registro de cliente (persona)
const modalRegisterCustomer = new bootstrap.Modal($('#registerCustomer'));

// Abrir el formulario de registro de cliente (persona)
Livewire.on('open-modal-register-customer', function() {
    modalRegisterCustomer.show();
});

// Cerrar el formulario de registro de cliente (persona)
Livewire.on('close-modal-register-customer', function() {
    modalRegisterCustomer.hide();
});

/* Generar impresión de factura de venta realizada */
Livewire.on('printTicket', function(movement, changeAmount) {

    // Lee el valor de cambio actual del input deshabilitado
    let changeAmount = $('#change_value').val();  

    Swal.fire({
        title: '¿Desea imprimir esta venta?',
        html: '<div class="text-center">' +
                  '<h5 class="text-muted">Debe devolver al cliente:</h5>' +
                  '<h1 class="text-success fw-bold">' + changeAmount + '</h1>' +
              '</div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, imprimir',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            try {
                print_sale(movement); // tu función de impresión
            } catch (error) {
                toastr.options.timeOut = 0;
                toastr.options.closeButton = true;
                toastr.error('Es posible que no esté en ejecución el plugin_impresora_termica en el equipo.', 'Error de impresión');
            }
        }
    });
});