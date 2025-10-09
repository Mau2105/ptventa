# Catálogo funcional de módulos

Este documento describe las funcionalidades disponibles en cada módulo de la plataforma SICEFA. Sirve como referencia rápida para comprender qué resuelve cada paquete, qué rutas expone y qué dependencias internas utiliza. También está disponible públicamente desde la aplicación en la ruta `/docs/modules`.

## SICA — Sistema Integral de Centros de Formación
- **Propósito:** actuar como módulo núcleo que centraliza datos maestros (personas, aprendices, cursos, eventos) y tableros administrativos.
- **Rutas principales:** el prefijo `/sica` expone página pública, contacto, créditos, un panel administrativo y un tablero de asistencia a eventos protegidos por el middleware `lang`.【F:Modules/SICA/Routes/web.php†L1-L17】
- **Controladores clave:** `SICAController` calcula métricas globales (personas, aprendices, aplicaciones, usuarios, roles, cursos) y renderiza vistas públicas y tableros de administración/asistencia con estadísticas detalladas de eventos y grupos poblacionales.【F:Modules/SICA/Http/Controllers/SICAController.php†L8-L89】
- **Dependencias compartidas:** todas las entidades (`Person`, `Apprentice`, `Course`, `EventAttendance`, etc.) son reutilizadas por módulos como PTVENTA, SIGAC y SENAEMPRESA para permisos, inventario y asistencia.

## PTVENTA — Punto de venta institucional
- **Flujos cubiertos:** administración de catálogo de productos, control de inventario, apertura/cierre de caja, registro de ventas, generación de reportes y seguimiento de movimientos para administrador y cajero.
- **Rutas:** agrupadas bajo `/ptventa` con variantes para administrador/cajero, protegidas por `lang`. Secciona vistas de inventario, ventas, caja y movimientos para ambos roles.【F:Modules/PTVENTA/Routes/web.php†L16-L103】
- **Back-office:** controladores especializados para KPIs del dashboard, inventario, ventas, elementos, caja y movimientos garantizan integridad transaccional y uso de helpers comunes (PUW).【F:Modules/PTVENTA/Http/Controllers/PTVENTAController.php†L17-L132】【F:Modules/PTVENTA/Http/Controllers/InventoryController.php†L16-L347】【F:Modules/PTVENTA/Http/Controllers/SaleController.php†L17-L200】
- **Interfaces dinámicas:** componentes Livewire gestionan el proceso completo de venta y ajustes de inventario en tiempo real, interactuando con entidades de SICA.【F:Modules/PTVENTA/Http/Livewire/Sale/GenerateSale.php†L25-L200】
- **Documentación ampliada:** consulta `Modules/PTVENTA/Docs/flow.md` para un recorrido detallado del módulo.

## BOLMETEOR — Estación meteorológica
- **Visión general:** ofrece un portal público y un panel administrativo para consultar, cargar y depurar datos climáticos capturados por sensores del centro.
- **Rutas públicas/admin:** bajo los prefijos `/bolmeteor` y `/bolmeteor/admin` se sirven vistas para datos climáticos, gráficos, cargue y gestión de registros. Incluye endpoints específicos para visualizaciones y mantenimiento vía AJAX.【F:Modules/BOLMETEOR/Routes/web.php†L16-L57】
- **Gestión de datos:** `BOLMETEORController` entrega tablas dinámicas de lecturas con DataTables, validación y operaciones CRUD, mientras que `GraphicsController` permite generar reportes estadísticos (máximos, mínimos, promedios, sumatorias) agrupados por periodo y variables meteorológicas.【F:Modules/BOLMETEOR/Http/Controllers/BOLMETEORController.php†L17-L86】【F:Modules/BOLMETEOR/Http/Controllers/GraphicsController.php†L18-L146】
- **Carga masiva:** soporte para importación de archivos y generación de gráficos especializados (rosas de viento) basados en agregaciones SQL.【F:Modules/BOLMETEOR/Http/Controllers/GraphicsController.php†L147-L213】

## CEFAMAPS — Cartografía institucional
- **Objetivo:** visualizar y administrar ambientes de formación, sectores y unidades productivas en un mapa interactivo.
- **Rutas:** el prefijo `/cefamaps` ofrece la vista pública y un dashboard administrativo para gestionar recursos georreferenciados.【F:Modules/CEFAMAPS/Routes/web.php†L1-L13】
- **Panel administrador:** `AdminController` consolida datos de unidades productivas, sectores, ambientes y roles desde SICA para alimentar el panel de control y formularios de geolocalización.【F:Modules/CEFAMAPS/Http/Controllers/AdminController.php†L9-L28】

## CPD — Centro de Procesamiento de Datos
- **Contenido:** provee un portal informativo del CPD, incluyendo página principal y catálogo de metadatos disponibles para consulta pública.
- **Rutas:** bajo `/cpd` se publican `home` y `metada`, ambas internacionalizadas.【F:Modules/CPD/Routes/web.php†L1-L12】
- **Controlador:** `CPDController` arma la vista informativa y lista de metadatos consultando la entidad `Data` para exponer recursos disponibles.【F:Modules/CPD/Http/Controllers/CPDController.php†L8-L25】

## EVS — Electoral Voting System
- **Funcionalidades:** gestiona procesos electorales completos, desde inscripción de autorizados, validación de votantes, registro del voto, tarjetón digital y resultados públicos.
- **Rutas:** `/evs` presenta landing, formularios de voto, normatividad, resultados, secciones de desarrolladores y paneles de jurados/administrador; `/evs/juries` maneja autenticación, consultas y reportes para jurados.【F:Modules/EVS/Routes/web.php†L1-L46】
- **Lógica de voto:** `EVSController` valida credenciales, controla vigencia del proceso, emite el tarjetón con candidatos activos y registra el voto, desactivando al autorizado tras sufragar.【F:Modules/EVS/Http/Controllers/EVSController.php†L9-L97】
- **Resultados y normatividad:** el mismo controlador agrega resultados por elección y expone vistas informativas.【F:Modules/EVS/Http/Controllers/EVSController.php†L99-L137】

## SENAEMPRESA — Gestión de turnos rutinarios
- **Objetivo:** asignar, registrar y actualizar turnos rutinarios para aprendices vinculados a cursos de la unidad productiva.
- **Rutas:** agrupadas en `/senaempresa`, ofrecen inicio, gestión de turnos, búsqueda por curso, asignación, listado y actualización de fechas.【F:Modules/SENAEMPRESA/Routes/web.php†L12-L26】
- **Operación:** `AsistenciaTurnosController` consulta cursos desde SICA, permite seleccionar aprendices por curso, crear registros de asistencia, asociar múltiples aprendices a un turno y actualizar fechas según necesidad.【F:Modules/SENAEMPRESA/Http/Controllers/AsistenciaTurnosController.php†L8-L120】

## SIGAC — Sistema de Gestión Académica y Control
- **Alcance:** soporta registro y consulta de asistencias para aplicaciones internas, así como paneles de horarios de instructores.
- **Rutas:** `sigac/index` sirve la portada pública, mientras los prefijos `/sigac/attendance` y `/sigac/schedule` habilitan formularios de registro/consulta de asistencia y consulta de horarios respectivamente.【F:Modules/SIGAC/Routes/web.php†L1-L34】
- **Controladores:** `AttendanceController` y `ScheduleInstructorController` componen vistas con listados de aplicaciones (`App`) para parametrizar reportes y registros de asistencia.【F:Modules/SIGAC/Http/Controllers/AttendanceController.php†L8-L35】【F:Modules/SIGAC/Http/Controllers/ScheduleInstructorController.php†L8-L35】

## TILABS — Laboratorios TIC
- **Propósito:** administrar laboratorios tecnológicos, inventario de equipos, préstamos, devoluciones y seguimiento de transacciones.
- **Rutas:** bajo `/tilabs` se ofrecen vistas públicas (inicio, desarrolladores, acerca de) y paneles internos para dashboard, laboratorios, inventario, préstamos, devoluciones y movimientos.【F:Modules/TILABS/Routes/web.php†L1-L23】
- **Controlador:** `TILABSController` centraliza la generación de vistas para cada sección administrativa, configurando los títulos y layouts que consumirán componentes especializados dentro del módulo.【F:Modules/TILABS/Http/Controllers/TILABSController.php†L8-L55】

---

Cada módulo sigue la convención de rutas `cefa.<modulo>.*` y reutiliza la capa de datos del núcleo SICA, por lo que cualquier nueva funcionalidad debe coordinarse con esos modelos compartidos. Utiliza esta guía para ubicar rápidamente la responsabilidad de cada paquete antes de profundizar en el código.
