<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['lang'])->group(function () {  // Middleware para la internacionalización (manejo de idiomas) y verificación de permisos y roles
    Route::prefix('ptventa')->group(function () {  // Agrega el prefijo en la url (sicefa.test/ptventa/...)

        // Rutas generales para el módulo PTVENTA
        Route::controller(PTVENTAController::class)->group(function () {
            Route::get('index', 'index')->name('cefa.ptventa.index'); // Vista principal y pública de la aplicación (Pública)
            Route::get('developers', 'devs')->name('cefa.ptventa.devs'); // Vista de créditos y desarrolladores, pública de la aplicación (Pública)
            Route::get('information', 'info')->name('cefa.ptventa.info'); // Vista más info sobre PTVENTA y pública de la aplicación (Pública)
            Route::get('admin', 'admin')->name('ptventa.admin.index'); // Vista principal del Administrador (Administrador)
            Route::get('cashier', 'cashier')->name('ptventa.cashier.index'); // Vista principal del Cajero (Cajero)
            Route::get('admin/configuration', 'configuration')->name('ptventa.admin.configuration.index'); // Vista de configuración, como generar un ticket o factura de prueba y verificar la conexión de la impresora, etc. (Administrador)
            Route::get('cashier/configuration', 'configuration')->name('ptventa.cashier.configuration.index'); // Vista de configuración, como generar un ticket o factura de prueba y verificar la conexión de la impresora, etc. (Cajero)
        });

        // Rutas para Inventario
        Route::controller(InventoryController::class)->group(function () {
            Route::get('admin/inventory/index', 'index')->name('ptventa.admin.inventory.index');
            Route::get('cashier/inventory/index', 'index')->name('ptventa.cashier.inventory.index');
            Route::get('admin/inventory/create', 'create')->name('ptventa.admin.inventory.create');
            Route::get('cashier/inventory/create', 'create')->name('ptventa.cashier.inventory.create');
            Route::get('admin/inventory/status', 'status')->name('ptventa.admin.inventory.status');
            Route::get('cashier/inventory/status', 'status')->name('ptventa.cashier.inventory.status');
            Route::get('admin/inventory/low', 'low_create')->name('ptventa.admin.inventory.low');
            Route::get('cashier/inventory/low', 'low_create')->name('ptventa.cashier.inventory.low');
            // Ruta para el panel de reportes (Administrador)
            Route::get('admin/reports/index', 'reports')->name('ptventa.admin.reports.index');
            // Ruta para el panel de reportes (Cajero)
            Route::get('cashier/reports/index', 'reports')->name('ptventa.cashier.reports.index');
            // Nueva ruta para generar PDF de inventario actual (Administrador) - Resuelve el error
            Route::post('admin/reports/inventory/pdf', 'generateInventoryPDF')->name('ptventa.admin.reports.inventory.generate.pdf');
            // Nueva ruta para generar PDF de inventario actual (Cajero)
            Route::post('cashier/reports/inventory/pdf', 'generateInventoryPDF')->name('ptventa.cashier.reports.inventory.generate.pdf');
            // Ruta para el formulario de entradas de inventario (Administrador)
            Route::get('admin/reports/inventory/entries', 'showInventoryEntriesForm')->name('ptventa.admin.reports.inventory.entries');
            // Ruta para generar las entradas de inventario (vista con datos) (Administrador)
            Route::post('admin/reports/inventory/entries/generate', 'generateInventoryEntries')->name('ptventa.admin.reports.inventory.entries.generate');
            // Ruta para generar el PDF de entradas de inventario (Administrador)
            Route::post('admin/reports/inventory/entries/pdf', 'generateInventoryEntriesPDF')->name('ptventa.admin.reports.inventory.entries.pdf');
            // Ruta para el formulario de entradas de inventario (Cajero)
            Route::get('cashier/reports/inventory/entries', 'showInventoryEntriesForm')->name('ptventa.cashier.reports.inventory.entries');
            // Ruta para generar las entradas de inventario (vista con datos) (Cajero)
            Route::post('cashier/reports/inventory/entries/generate', 'generateInventoryEntries')->name('ptventa.cashier.reports.inventory.entries.generate');
            // Ruta para generar el PDF de entradas de inventario (Cajero)
            Route::post('cashier/reports/inventory/entries/pdf', 'generateInventoryEntriesPDF')->name('ptventa.cashier.reports.inventory.entries.pdf');
            // Ruta para el formulario de consulta de ventas (Administrador)
            Route::get('admin/reports/sales', 'showSalesForm')->name('ptventa.admin.reports.sales');
            // Ruta para procesar la consulta de ventas (Administrador)
            Route::post('admin/reports/sales/generate', 'generateSales')->name('ptventa.admin.reports.generate.sales');
            // Ruta para generar el PDF de ventas completas (Administrador)
            Route::post('admin/reports/sales/pdf', 'generateSalesPDF')->name('ptventa.admin.reports.generate.sales.pdf');
        });

        // Rutas para Ventas
        Route::controller(SaleController::class)->group(function () {
            Route::get('admin/sale/index', 'index')->name('ptventa.admin.sale.index'); // Vista principal de ventas realizadas en sesión de caja (Administrador)
            Route::get('cashier/sale/index', 'index')->name('ptventa.cashier.sale.index'); // Vista principal de ventas realizadas en sesión de caja (Cajero)
            Route::get('admin/sale/register', 'register')->name('ptventa.admin.sale.register'); // Formulario de registro de venta (Administrador)
            Route::get('cashier/sale/register', 'register')->name('ptventa.cashier.sale.register'); // Formulario de registro de venta (Cajero)
            Route::get('admin/sale/show/{movement}', 'show')->name('ptventa.admin.movements.sale.show'); // Ver detalle de venta (Administrador)
            Route::get('cashier/sale/show/{movement}', 'show')->name('ptventa.cashier.movements.sale.show'); // Ver detalle de venta (Cajero)
        });

        // Rutas para Elementos
        Route::controller(ElementController::class)->group(function () {
            Route::get('admin/element/index', 'index')->name('ptventa.admin.element.index'); // Vista principal de productos (Administrador)
            Route::get('admin/element/edit/{element}', 'edit')->name('ptventa.admin.element.edit'); // Formulario para actualizar producto (Administrador)
            Route::post('admin/element/update/{element}', 'update')->name('ptventa.admin.element.update'); // Actualizar producto (Administrador)
            Route::get('admin/element/create', 'create')->name('ptventa.admin.element.create'); // Formulario de registro de producto (Administrador)
            Route::post('admin/element/store', 'store')->name('ptventa.admin.element.store'); // Registrar producto (Administrador)
        });

        // Rutas para Caja
        Route::controller(CashController::class)->group(function () {
            Route::get('admin/cash/index', 'index')->name('ptventa.admin.cash.index'); // Vista principal de sesión de caja activa e historico de sesiones de caja (Administrador)
            Route::get('cashier/cash/index', 'index')->name('ptventa.cashier.cash.index'); // Vista principal de sesión de caja activa e historico de sesiones de caja (Cajero)
            Route::post('admin/cash/store', 'store')->name('ptventa.admin.cash.store'); // Registrar caja cuando no hay ninguna activa (Administrador)
            Route::post('cashier/cash/store', 'store')->name('ptventa.cashier.cash.store'); // Registrar caja cuando no hay ninguna activa (Cajero)
            Route::post('admin/cash/close', 'close')->name('ptventa.admin.cash.close'); // Cerrar sesión de caja (Administrador)
            Route::post('cashier/cash/close', 'close')->name('ptventa.cashier.cash.close'); // Cerrar sesión de caja (Cajero)
        });

        // Rutas para movements ó historico de cajas
        Route::controller(MovementController::class)->group(function () {
            Route::get('admin/movement/index', 'index')->name('ptventa.admin.movements.index'); // Vista principal de historico de movimientos (Administrador)
            Route::get('cashier/movement/index', 'index')->name('ptventa.cashier.movements.index'); // Vista principal de historico de movimientos (Cajero)
            Route::post('admin/movement/consult', 'consult')->name('ptventa.admin.movements.consult'); // Consultar movimientos por fecha y actor (Administrador)
            Route::post('cashier/movement/consult', 'consult')->name('ptventa.cashier.movements.consult'); // Consultar movimientos por fecha y actor (Cajero)
        });
    });
});