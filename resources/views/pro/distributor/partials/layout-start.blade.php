{{-- Module 3 - En-tête commun de l'espace distributeur (onglets + messages flash). Ouvre un .container que la page referme --}}
<div class="distributor-header">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h1 class="h3 mb-1"><i class="fa-solid fa-truck"></i> Espace Distributeur</h1>
        <p class="mb-0 opacity-75">{{ auth()->user()->company_name }} · Traçabilité logistique & empreinte carbone</p>
      </div>
      <a href="{{ route('pro.dashboard') }}" class="btn btn-outline-light btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Tableau de bord
      </a>
    </div>
    <ul class="nav nav-pills distributor-tabs mt-3">
      <li class="nav-item">
        <a class="nav-link @if(request()->routeIs('pro.distributor.shipments.*')) active @endif"
           href="{{ route('pro.distributor.shipments.index') }}">
          <i class="fa-solid fa-route"></i> Expéditions
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link @if(request()->routeIs('pro.distributor.points-of-sale.*')) active @endif"
           href="{{ route('pro.distributor.points-of-sale.index') }}">
          <i class="fa-solid fa-store"></i> Points de vente
        </a>
      </li>
    </ul>
  </div>
</div>

<div class="container">
  @foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $class)
    @if(session($key))
      <div class="alert alert-{{ $class }} alert-dismissible fade show" role="alert">
        {{ session($key) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif
  @endforeach

  @if($errors->any())
    <div class="alert alert-danger">
      <strong>Le formulaire contient des erreurs :</strong>
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
