<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SICEFA</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
        @include('layouts/partials/head')

        <!-- Estilos adicionales para uniformidad y galería (adaptado de tu Livewire) -->
        <style>
            .portfolio-img {
                height: 200px;
                overflow: hidden;
            }
            .portfolio-img img {
                width: 100%;
                height: 100%;
                object-fit: cover; /* Mismo tamaño y recorte proporcional */
                transition: transform 0.3s;
            }
            .portfolio-img:hover img {
                transform: scale(1.1);
            }
            .portfolio-info {
                background: #fff;
                padding: 15px;
                text-align: center;
                height: 150px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            .ribbon-wrapper {
                position: absolute;
                top: 10px;
                right: 10px;
            }
            .ribbon {
                background: #28a745;
                color: white;
                padding: 5px 10px;
                font-size: 14px;
                border-radius: 5px;
            }
            /* Filtros Isotope (asegúrate JS esté en scripts) */
        </style>
    </head>
    <body class="antialiased">
        
        @include('layouts/partials/HeaderDesing')

        <main id="main">
            <br>
            
            @include('layouts/partials/modules')
            
            <!-- ======= Sección de Productos en Home ======= -->
            <section id="productos-home" class="portfolio sinfondo">
                <div class="container" data-aos="fade-up">

                    <div class="section-title">
                        <h2>Productos Actuales y Recientes</h2>
                        <p>Explora nuestros productos en venta / animales de corral</p>
                    </div>

                    <ul id="portfolio-flters" class="d-flex justify-content-center" data-aos="fade-up" data-aos-delay="100">
                        <li data-filter="*" class="filter-active">Todos</li>
                        <li data-filter=".filter-carnicos">Carnicos</li>
                        <li data-filter=".filter-panaderia">Panaderia</li>
                        <li data-filter=".filter-fruver">Frutas y verduras</li>
                        <li data-filter=".filter-lacteos">Lacteos</li>
                        <li data-filter=".filter-animales">Animales</li>
                    </ul>

                    <div class="row portfolio-container" data-aos="fade-up" data-aos-delay="200">
                        @foreach($elements as $element)
                            <?php 
                                // Mapeo de filtro: lowercase y sin espacios, ajusta si categories difieren
                                $filterClass = 'filter-' . strtolower(str_replace(' ', '', $element->category->name ?? 'uncategorized'));
                            ?>
                            <div class="col-lg-4 col-md-6 portfolio-item {{ $filterClass }}">
                                <a href="{{ $element->image ? asset($element->image) : asset('modules/sica/images/sinImagen.png') }}" class="portfolio-lightbox preview-link">
                                    <div class="portfolio-img text-center">
                                        <img src="{{ $element->image ? asset($element->image) : asset('modules/sica/images/sinImagen.png') }}" class="img-fluid" alt="{{ $element->name }}">
                                        <div class="ribbon-wrapper">
                                            <div class="ribbon bg-success">
                                                <strong>{{ '$ ' . number_format($element->price) }}</strong> <!-- Formato precio, ajusta si tienes helper -->
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <div class="portfolio-info">
                                    <h4>{{ $element->name }}</h4>
                                    <p>{{ $element->category->name ?? 'Sin categoría' }}</p>
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ $element->image ? asset($element->image) : asset('modules/sica/images/sinImagen.png') }}" data-gallery="portfolioGallery" class="portfolio-lightbox" title="Ampliar"><i class="bx bx-plus"></i> Ampliar</a>
                                        <a href="{{ route('ptventa.admin.element.index') }}" class="details-link" title="Ir a Punto de Venta"> <!-- Ajusta ruta exacta, ej. ptventa.[role].element.index -->
                                            <i class="bx bx-link"></i> Ir a PTO Venta
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($elements->isEmpty())
                            <p class="text-center col-12">No hay productos disponibles por el momento.</p>
                        @endif
                    </div>

                </div>
            </section><!-- End Productos Section -->

        </main>

        @include('layouts/partials/footer')
        @include('layouts/partials/scripts')

        <!-- Inicializa Isotope y Lightbox si no está en scripts (agrega al final) -->
        <script>
            // Asumiendo jQuery e Isotope en tu template
            document.addEventListener('DOMContentLoaded', function() {
                var portfolioContainer = document.querySelector('.portfolio-container');
                var iso = new Isotope(portfolioContainer, {
                    itemSelector: '.portfolio-item',
                    layoutMode: 'fitRows'
                });

                var filters = document.querySelectorAll('#portfolio-flters li');
                filters.forEach(function(filter) {
                    filter.addEventListener('click', function() {
                        filters.forEach(f => f.classList.remove('filter-active'));
                        this.classList.add('filter-active');
                        iso.arrange({ filter: this.getAttribute('data-filter') });
                    });
                });

                // Lightbox (GLightbox o similar)
                const lightbox = GLightbox({ selector: '.portfolio-lightbox' });
            });
        </script>
    </body>
</html>