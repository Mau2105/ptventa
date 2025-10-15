<!-- Vendor JS Files -->
<script src="{{ asset('general/assets/vendor/aos/aos.js') }}"></script>
<script src="{{ asset('general/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('general/assets/vendor/glightbox/js/glightbox.min.js') }}"></script>
<script src="{{ asset('general/assets/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
<script src="{{ asset('general/assets/vendor/php-email-form/validate.js') }}"></script>
<script src="{{ asset('general/assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('general/assets/vendor/waypoints/noframework.waypoints.js') }}"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3/dist/css/glightbox.min.css">

<!-- Template Main JS File -->
<script src="{{ asset('general/assets/js/main.js') }}"></script>
<!-- JQuery -->
<script src="{{ asset('AdminLTE/plugins/jquery/jquery.min.js') }}"></script>
<!-- Admin LTE -->
<script src="{{ asset('AdminLTE/dist/js/adminlte.js') }}"></script>

{{-- Inicialización específica para portfolio si main.js no lo cubre --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Isotope para filtros
        var elem = document.querySelector('.portfolio-container');
        if (elem) {
            var iso = new Isotope(elem, {
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
        }

        // GLightbox para ampliar
        const lightbox = GLightbox({ selector: '.portfolio-lightbox' });

        // Tu loader existente
        var contenedor = document.getElementById('contenedor_carga');
        if (contenedor) {
            contenedor.style.visibility = 'hidden';
            contenedor.style.opacity = '0';
        }
    });
</script>