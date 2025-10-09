# Guía funcional del repositorio SICEFA/PTVENTA

Esta guía describe la estructura y las funcionalidades principales del repositorio para que nuevos desarrolladores puedan orientarse rápidamente. Está pensada como documento público y de libre consulta y se publica automáticamente en la ruta `/docs` de la plataforma una vez desplegada.

## 1. Arquitectura general
- **Framework base:** Laravel 8, configurado para PHP 7.3+ u 8.x y con autenticación tradicional (`Auth::routes`) y middleware de internacionalización `lang` aplicado a todas las rutas públicas.
- **Estructura modular:** se utiliza `nwidart/laravel-modules` junto con `mhmiton/laravel-modules-livewire` para aislar funcionalidades en módulos independientes (`Modules/`). Cada módulo posee su propio `module.json`, service provider, rutas, controladores, vistas, entidades y assets.
- **UI y Front-end:** Laravel Mix compila assets con Bootstrap 5, Livewire, DataTables, SweetAlert2 y dependencias adicionales declaradas en `package.json`. Esto permite interfaces reactivas y tablas dinámicas en todos los módulos.
- **Servicios compartidos:** el middleware `LangMiddleware` sincroniza idioma y verificación de permisos en base a nombres de ruta, centralizando la autorización y acceso por rol.

## 2. Módulos funcionales
A modo de resumen, la plataforma agrupa sus soluciones en módulos autocontenidos. Consulta el [Catálogo funcional de módulos](modules.md) para conocer en detalle las responsabilidades, rutas y controladores de cada uno.

### Visión rápida
- **SICA:** núcleo de datos maestros, permisos y dashboards institucionales.
- **PTVENTA:** operación de punto de venta (inventario, caja, ventas, reportes) para administrador y cajero.
- **BOLMETEOR:** monitoreo meteorológico con generación de gráficas estadísticas y carga masiva de lecturas.
- **CEFAMAPS:** cartografía del centro con administración de ambientes y unidades productivas.
- **CPD:** portal informativo y catálogo de metadatos del centro de procesamiento de datos.
- **EVS:** sistema de votaciones electrónicas con flujo completo de sufragio y reportes para jurados.
- **SENAEMPRESA:** gestión de turnos rutinarios de aprendices por curso.
- **SIGAC:** registro y consulta de asistencias, horarios de instructores y reportes académicos.
- **TILABS:** administración de laboratorios TIC, inventario y movimientos de préstamo/devolución.

## 3. Dependencias destacadas
- **Reportes PDF y ofimática:** `barryvdh/laravel-dompdf`, `mpdf/mpdf`, `maatwebsite/excel`, `mikehaertl/php-pdftk` permiten exportar informes e integrar formatos institucionales (usados por PTVENTA, SIGAC y BOLMETEOR).
- **Gestión de archivos e imágenes:** `alexusmai/laravel-file-manager`, `unisharp/laravel-filemanager` y `intervention/image` soportan la carga de imágenes de productos, tarjetones y recursos multimedia.
- **Impresión de tickets:** `mike42/escpos-php` y `qz-tray` habilitan la integración con impresoras POS en PTVENTA.
- **Interfaces reactivas:** Livewire, DataTables y SweetAlert2 facilitan flujos interactivos sin recargar página, presentes en PTVENTA, SIGAC y TILABS.

## 4. Puesta en marcha local
1. Clonar el repositorio y copiar el archivo `.env.example` a `.env` ajustando credenciales de base de datos y servicios externos.
2. Ejecutar `composer install` y `npm install` para obtener dependencias PHP/JS.
3. Generar la llave de aplicación con `php artisan key:generate` (el script `post-create-project-cmd` también la crea en instalaciones nuevas).
4. Ejecutar migraciones y seeders necesarios (por ejemplo `php artisan migrate --seed`) para poblar catálogos iniciales.
5. Compilar assets con `npm run dev` (o `npm run prod` en producción).
6. Levantar el servidor con `php artisan serve` y acceder a las rutas descritas.

## 5. Buenas prácticas para contribuir
- Registrar nuevas funcionalidades dentro del módulo correspondiente, creando controladores, rutas y vistas en su propio espacio (`Modules/<Nombre>/`).
- Respetar el middleware `lang` y registrar nombres de ruta siguiendo la convención `cefa.<modulo>.<feature>` para integrar con el control de permisos de `LangMiddleware`.
- Cuando se creen reportes o procesos transaccionales, reutilizar las entidades y helpers existentes (por ejemplo `Modules/PTVENTA/Http/Controllers/PUW.php`) para mantener coherencia de datos.
- Actualizar la documentación (este archivo y, si aplica, los docs específicos del módulo) para mantener la guía pública al día.

Con esta referencia los desarrolladores junior pueden identificar rápidamente qué hace cada módulo, qué dependencias utiliza el proyecto y cómo preparar un entorno de trabajo productivo.
