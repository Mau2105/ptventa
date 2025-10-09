# SICEFA - Plataforma modular del Centro de Formación

Este repositorio contiene la plataforma Laravel que integra los módulos desarrollados por el Centro de Formación para apoyar sus procesos académicos, administrativos y de servicios. El objetivo es centralizar soluciones como punto de venta, votaciones, gestión de laboratorios, reportes académicos y monitoreo ambiental dentro de una misma base de código.

## Características principales
- **Arquitectura modular:** basada en `nwidart/laravel-modules`, cada solución vive en `Modules/<Nombre>` con su propio ciclo de rutas, controladores, vistas y assets.
- **Integraciones clave:** incluye Livewire para experiencias reactivas, generación de reportes PDF/Excel, impresión POS mediante ESC/POS y administradores de archivos para gestionar recursos multimedia.
- **Internacionalización y permisos:** el middleware `lang` aplica traducciones dinámicas y verifica accesos según el nombre de cada ruta, permitiendo separar vistas públicas y privadas por rol.

## Módulos destacados
- **PTVENTA:** punto de venta con administración de inventario, ventas, caja y reportes (ver documentación detallada en `Modules/PTVENTA/Docs/flow.md`).
- **BOLMETEOR:** visualización y administración de datos meteorológicos del centro.
- **CEFAMAPS:** mapa institucional con panel administrativo.
- **CPD:** portal informativo del centro de procesamiento de datos.
- **EVS:** sistema electoral con votación en línea y administración de jurados.
- **SENAEMPRESA:** gestión de turnos rutinarios para aprendices.
- **SIGAC:** seguimiento académico y control de asistencia.
- **TILABS:** control de laboratorios TIC e inventario de préstamos.

Consulta la [Guía funcional del repositorio](Docs/README.md) para conocer el detalle de cada módulo, dependencias y buenas prácticas de contribución; y revisa el [Catálogo funcional de módulos](Docs/modules.md) para un desglose completo de responsabilidades y flujos por paquete. Desde la aplicación en ejecución, toda esta información está disponible públicamente en `/docs`.

## Puesta en marcha rápida
1. Clonar el repositorio y copiar `.env.example` a `.env` ajustando credenciales.
2. Instalar dependencias con `composer install` y `npm install`.
3. Generar la llave de la aplicación con `php artisan key:generate`.
4. Ejecutar migraciones/seeders necesarios y compilar assets con `npm run dev`.
5. Levantar el servidor con `php artisan serve`.

## Contribución
- Desarrolla nuevas funcionalidades dentro del módulo correspondiente manteniendo la estructura modular.
- Sigue las convenciones de nombres de ruta `cefa.<modulo>.<feature>` para integrarte con el sistema de permisos.
- Actualiza la documentación cuando se añadan características relevantes.

## Licencia
Este proyecto se distribuye bajo la licencia MIT incluida en el repositorio.
