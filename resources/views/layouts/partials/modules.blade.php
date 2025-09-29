<!-- ======= Services Section ======= -->
<section id="modules" class="services section-bg">
  <div class="container" data-aos="fade-up">

    <div class="section-title">
      <h2>Aplicaciones</h2>
      <p>Soluciones de software para nuestro centro</p>
    </div>

    <div class="row justify-content-center">
      @foreach($apps as $app)
        <style>
          .colorapp{{ $app->id }} {
            color: inherit;
            transition: color 0.3s ease;
          }

          .services .icon-box:hover .colorapp{{ $app->id }} {
            color: {{ $app->color }} !important;
          }
        </style>

        <div class="col-xl-3 col-md-4 col-sm-6 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="100">
          <div class="icon-box w-100 text-center">
            <div class="icon mb-3">
              <a href="{{ url($app->url) }}" class="text-decoration-none">
                <i class="colorapp{{ $app->id }} {{ $app->icon }}" style="font-size: 2.5rem;"></i>
              </a>
            </div>
            <h4>
              <a href="{{ url($app->url) }}" class="colorapp{{ $app->id }}">
                {{ $app->name }}
              </a>
            </h4>
            <p>
              @if(session('lang') === 'en')
                {{ $app->description_english }}
              @else
                {{ $app->description }}
              @endif
            </p>
          </div>
        </div>
      @endforeach
    </div>

  </div>
</section>
<!-- End Services Section -->
