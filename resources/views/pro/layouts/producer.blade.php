@extends('frontend.layouts.app')

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/producer.css') }}">
  @stack('styles')
@endsection

@section('content')
<div class="producer-shell">
  <div class="container">

    <nav class="producer-nav" aria-label="Navigation producteur">
      <div class="producer-nav__brand">
        <i class="fa-solid fa-tractor"></i>
        <div>
          <strong>Espace Producteur</strong>
          <small>{{ auth()->user()->company_name }}</small>
        </div>
      </div>
      <div class="producer-nav__links">
        <a href="{{ route('pro.dashboard') }}" class="{{ request()->routeIs('pro.dashboard') ? 'active' : '' }}"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
        <a href="{{ route('pro.farms.index') }}" class="{{ request()->routeIs('pro.farms.*') ? 'active' : '' }}"><i class="fa-solid fa-wheat-awn"></i> Exploitations</a>
        <a href="{{ route('pro.harvests.index') }}" class="{{ request()->routeIs('pro.harvests.*') ? 'active' : '' }}"><i class="fa-solid fa-basket-shopping"></i> Récoltes</a>
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

    @yield('producer-content')
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

@include('pro.partials.chatbot')
@endsection

@section('extra-js')
  <script>
    window.AGRI = {
      csrf: @json(csrf_token()),
      chatUrl: @json(route('pro.ai.chat')),
      descriptionUrl: @json(route('pro.ai.farm-description')),
      harvestDescriptionUrl: @json(route('pro.ai.harvest-description')),
    };
  </script>
  <script src="{{ asset('frontend/js/producer.js') }}"></script>
  @stack('scripts')
@endsection
