@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    Documentación pública
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('docs.overview') }}" class="list-group-item list-group-item-action {{ request()->routeIs('docs.overview') ? 'active' : '' }}">
                        Guía general
                    </a>
                    <a href="{{ route('docs.modules') }}" class="list-group-item list-group-item-action {{ request()->routeIs('docs.modules') ? 'active' : '' }}">
                        Módulos
                    </a>
                    <a href="{{ route('docs.ptventa') }}" class="list-group-item list-group-item-action {{ request()->routeIs('docs.ptventa') ? 'active' : '' }}">
                        Flujo PTVENTA
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h3 mb-4">{{ $title }}</h1>
                    <div class="markdown-body">
                        {!! $content !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown-light.min.css" integrity="sha512-Z79DbStO8CAZuYSWwhCI9VCqeXp9JED1smR1ziIOgLqVwb9hoTLMdS00OUgQ3fDUL2z2dbQcR0aXlEDk+vwxVw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    .markdown-body h1,
    .markdown-body h2,
    .markdown-body h3 {
        color: #156830;
    }
    .markdown-body {
        font-size: 0.95rem;
    }
</style>
@endpush
