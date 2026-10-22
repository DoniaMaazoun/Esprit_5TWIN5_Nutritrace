@extends('pro.layouts.processor')

@section('title', 'Mes Lots de Production | Farm to Fork')

@section('processor-content')

<style>
  .catalog-hero { background: linear-gradient(135deg, var(--processor-primary-dark), var(--processor-primary)); color: #fff; padding: 44px 0 80px; }
  .catalog-hero h1 { color: #fff; font-size: 2.1rem; margin-bottom: 6px; }
  .catalog-hero p { opacity: .85; margin: 0; max-width: 640px; }
  .catalog-body { margin-top: -50px; padding-bottom: 70px; }
  .catalog-body .btn { padding: .45rem .9rem; font-size: .88rem; border: 1px solid transparent; }
  .catalog-body .btn-success { background: var(--processor-primary); border-color: var(--processor-primary); }
  .catalog-body .btn-outline-success { border-color: var(--processor-primary); color: var(--processor-primary); }
  .catalog-body .btn-outline-success:hover { background: var(--processor-primary); color: #fff; }
  .catalog-filters { grid-template-columns: 2fr repeat(2, 1fr) 1fr auto; }
  @media (max-width: 992px) { .catalog-filters { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 576px) { .catalog-filters { grid-template-columns: 1fr; } }
  .smart-search { background: #fff; border-radius: 18px; padding: 16px; box-shadow: var(--shadow-md); border: 1px solid rgba(45,106,79,.15); margin-bottom: 18px; }
  .smart-search__box { display: flex; align-items: center; gap: 10px; border: 1.5px solid #e4defa; border-radius: 14px; padding: 4px 4px 4px 16px; background: linear-gradient(90deg, #faf8ff, #fff); }
  .smart-search__box:focus-within { border-color: var(--processor-primary); box-shadow: 0 0 0 4px rgba(45,106,79,.12); }
  .smart-search__box > i { color: var(--processor-primary); font-size: 1.15rem; }
  .smart-search__box input { flex: 1; border: 0; outline: 0; background: none; height: 46px; font-size: .98rem; min-width: 0; }
  .smart-search__box button { border: 0; height: 46px; padding: 0 20px; border-radius: 11px; background: linear-gradient(135deg, var(--processor-primary), #2D6A4F); color: #fff; font-family: var(--font-heading); font-weight: 600; }
  .smart-search__chips, .smart-search__examples { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 10px; font-size: .84rem; color: var(--gray); }
  .smart-search__chips em { font-style: normal; background: #f0f9f4; color: #1B4332; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
  .smart-search__chips a { margin-left: auto; color: var(--gray); }
  .smart-search__examples a { background: var(--gray-light); color: var(--gray-dark); padding: 3px 10px; border-radius: 999px; }
  .smart-search__examples a:hover { background: #f0f9f4; color: #1B4332; }
</style>

<section class="catalog-hero">
  <div class="container">
    <h1><i class="fa-solid fa-cubes"></i> Lots de Production</h1>
    <p>Gérez vos lots de produits finis. Créez, suivez et tracez vos productions avec QR codes.</p>
  </div>
</section>

<div class="container catalog-body">
  <div class="stat-grid">
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-cube"></i></div><div><div class="stat__value">{{ $stats['total_lots'] }}</div><div class="stat__label">Lots Total</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-check-circle"></i></div><div><div class="stat__value">{{ $stats['total_produced'] }}</div><div class="stat__label">Produits</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="stat__value">{{ $stats['close_to_expiry'] }}</div><div class="stat__label">À Venir</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-exclamation-circle"></i></div><div><div class="stat__value">{{ $stats['expired'] }}</div><div class="stat__label">Périmés</div></div></div>
  </div>

  <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2" style="margin-bottom: 28px; margin-top: 0;">
    <span><i class="fa-solid fa-circle-info"></i> Vous gérez vos lots de production finis. Chaque lot génère un QR code unique pour la traçabilité.</span>
    <a href="{{ route('pro.processor.production-lots.create') }}" class="btn btn-success btn-sm"><i class="fa-solid fa-plus"></i> Nouveau Lot</a>
  </div>

  {{-- Barre de recherche intelligente --}}
  <form method="GET" action="{{ route('pro.processor.production-lots.index') }}" class="smart-search" novalidate>
    <div class="smart-search__box">
      <i class="fa-solid fa-wand-magic-sparkles"></i>
      <input type="search" name="q" value="{{ $search ?? '' }}" maxlength="150" placeholder="Cherchez un lot, une recette, une date…" aria-label="Recherche intelligente">
      <button type="submit">Chercher</button>
    </div>
    @if(!empty($search))
      <div class="smart-search__chips">
        <span><i class="fa-solid fa-search"></i> Résultats pour :</span>
        <em>{{ $search }}</em>
        <a href="{{ route('pro.processor.production-lots.index') }}"><i class="fa-solid fa-xmark"></i> Effacer</a>
      </div>
    @else
      <div class="smart-search__examples">
        Exemples :
        <a href="{{ route('pro.processor.production-lots.index', ['q' => 'sauce']) }}">sauce</a>
        <a href="{{ route('pro.processor.production-lots.index', ['q' => 'produit']) }}">produit</a>
        <a href="{{ route('pro.processor.production-lots.index', ['status' => 'produced']) }}">produits</a>
      </div>
    @endif
  </form>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($productionLots->isEmpty())
    <div class="p-card empty-state">
      <i class="fa-solid fa-cubes d-block"></i>
      @if(!empty($search))
        <p>Aucun lot ne correspond à votre recherche.</p>
      @else
        <p>Aucun lot de production créé pour le moment.</p>
      @endif
      @if(!empty($search))
        <a href="{{ route('pro.processor.production-lots.index') }}" class="btn btn-outline-success">
          <i class="fa-solid fa-plus"></i> Voir tous mes lots
        </a>
      @else
        <a href="{{ route('pro.processor.production-lots.create') }}" class="btn btn-outline-success">
          <i class="fa-solid fa-plus"></i> Créer un Lot
        </a>
      @endif
    </div>
  @else
    <div class="lot-grid">
      @foreach($productionLots as $lot)
        @include('pro.processor.partials.production-lot-card', ['lot' => $lot])
      @endforeach
    </div>
  @endif

  @if(!$productionLots->isEmpty())
    <div class="mt-4 d-flex justify-content-center">
      {{ $productionLots->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>

@endsection
