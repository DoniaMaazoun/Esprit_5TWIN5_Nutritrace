@extends('frontend.layouts.app')

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/processor.css') }}">
  @stack('styles')
@endsection

@section('content')
<div class="processor-shell">
  <div class="container">

    <nav class="processor-nav" aria-label="Navigation transformateur">
      <div class="processor-nav__brand">
        <i class="fa-solid fa-industry"></i>
        <div>
          <strong>Espace Transformateur</strong>
          <small>{{ auth()->user()->company_name }}</small>
        </div>
      </div>
      <div class="processor-nav__links">
        <a href="{{ route('pro.dashboard') }}" class="{{ request()->routeIs('pro.dashboard') ? 'active' : '' }}"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
        <a href="{{ route('pro.processor.raw-materials.index') }}" class="{{ request()->routeIs('pro.processor.raw-materials.*') ? 'active' : '' }}"><i class="fa-solid fa-inbox"></i> Réception</a>
        <a href="{{ route('pro.processor.recipes.index') }}" class="{{ request()->routeIs('pro.processor.recipes.*') ? 'active' : '' }}"><i class="fa-solid fa-gears"></i> Recettes</a>
        <a href="{{ route('frontend.products') }}"><i class="fa-solid fa-store"></i> Tous les produits</a>
      </div>
    </nav>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif
    @if(session('warning'))
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-exclamation"></i> {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif

    @yield('processor-content')
  </div>
</div>

{{-- Confirmation de suppression --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmModalTitle"><i class="fa-solid fa-triangle-exclamation text-danger"></i> Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body js-confirm-text"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-danger js-confirm-ok"><i class="fa-solid fa-trash"></i> Supprimer</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('extra-js')
  <script src="{{ asset('frontend/js/processor.js') }}"></script>
  @stack('scripts')
@endsection
