{{-- Module 3 - Tableau de bord du distributeur affiché par /pro/dashboard (pro.dashboard est la vue du transformateur) --}}
@extends('frontend.layouts.app')

@section('title', $dashboardData['title'] . ' - ' . $companyName . ' | Farm to Fork')

@section('extra-css')
  @include('pro.distributor.partials.styles')
@endsection

@section('content')
<div class="distributor-page">
  <div class="distributor-header pb-4">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <h1 class="h3 mb-1"><i class="fa-solid {{ $dashboardData['icon'] }}"></i> {{ $dashboardData['title'] }}</h1>
          <p class="mb-0 opacity-75">{{ $companyName }} · {{ $user->getProTypeLabel() }} · N° TVA {{ $taxId }}</p>
        </div>
        <a href="{{ route('pro.distributor.dashboard') }}" class="btn btn-outline-light btn-sm">
          <i class="fa-solid fa-chart-pie"></i> Dashboard carbone
        </a>
      </div>
    </div>
  </div>

  <div class="container">
    @if(session('error'))
      <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <h2 class="h5 mb-3">Bienvenue, {{ $user->name }}</h2>

    <div class="row g-4">
      @foreach($dashboardData['modules'] as $module)
        <div class="col-md-4">
          <div class="card distributor-card h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="stat-value"><i class="fa-solid {{ $module['icon'] }}"></i></span>
                <span class="badge co2-badge">{{ $module['badge'] }}</span>
              </div>
              <h3 class="h5">{{ $module['title'] }}</h3>
              <p class="text-muted flex-grow-1">{{ $module['description'] }}</p>
              @if($module['route'] !== '#')
                <a href="{{ $module['route'] }}" class="btn btn-success align-self-start">
                  Accéder au module <i class="fa-solid fa-arrow-right"></i>
                </a>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
