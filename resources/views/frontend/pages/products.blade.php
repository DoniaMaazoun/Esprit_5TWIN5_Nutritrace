@extends('frontend.layouts.app')

@section('title', 'Produits tracés | Farm to Fork')
@section('description', 'Découvrez les produits récoltés par les producteurs tunisiens de Farm to Fork : origine, exploitation, date de récolte et qualité.')

@php
  use App\Models\Farm;
  use App\Models\Harvest;
  $user = auth()->user();
  $catUrl = fn ($id) => route('frontend.products', array_merge(request()->except(['page', 'category']), $id ? ['category' => $id] : []));
@endphp

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/producer.css') }}">
  <style>
    .catalog-hero { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); color: #fff; padding: 44px 0 80px; }
    .catalog-hero h1 { color: #fff; font-size: 2.1rem; margin-bottom: 6px; }
    .catalog-hero p { opacity: .85; margin: 0; max-width: 640px; }
    .catalog-body { margin-top: -50px; padding-bottom: 70px; }
    .catalog-body .btn { padding: .45rem .9rem; font-size: .88rem; border: 1px solid transparent; }
    .catalog-body .btn-success { background: var(--primary); border-color: var(--primary); }
    .catalog-body .btn-outline-success { border-color: var(--primary); color: var(--primary); }
    .catalog-body .btn-outline-success:hover { background: var(--primary); color: #fff; }
    .catalog-body .btn-outline-danger { border-color: #dc3545; }
    .catalog-filters { grid-template-columns: 2fr repeat(4, 1fr) 1fr auto; }
    @media (max-width: 992px) { .catalog-filters { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 576px) { .catalog-filters { grid-template-columns: 1fr; } }
    .smart-search { background: #fff; border-radius: 18px; padding: 16px; box-shadow: var(--shadow-md); border: 1px solid rgba(124,58,237,.15); margin-bottom: 18px; }
    .smart-search__box { display: flex; align-items: center; gap: 10px; border: 1.5px solid #e4defa; border-radius: 14px; padding: 4px 4px 4px 16px; background: linear-gradient(90deg, #faf8ff, #fff); }
    .smart-search__box:focus-within { border-color: #7c3aed; box-shadow: 0 0 0 4px rgba(124,58,237,.12); }
    .smart-search__box > i { color: #7c3aed; font-size: 1.15rem; }
    .smart-search__box input { flex: 1; border: 0; outline: 0; background: none; height: 46px; font-size: .98rem; min-width: 0; }
    .smart-search__box button { border: 0; height: 46px; padding: 0 20px; border-radius: 11px; background: linear-gradient(135deg, #7c3aed, #2563eb); color: #fff; font-family: var(--font-heading); font-weight: 600; }
    .smart-search__chips, .smart-search__examples { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 10px; font-size: .84rem; color: var(--gray); }
    .smart-search__chips em { font-style: normal; background: #f3efff; color: #5b21b6; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
    .smart-search__chips a { margin-left: auto; color: var(--gray); }
    .smart-search__examples a { background: var(--gray-light); color: var(--gray-dark); padding: 3px 10px; border-radius: 999px; }
    .smart-search__examples a:hover { background: #f3efff; color: #5b21b6; }
    .cat-strip { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .cat-pill { --cat: var(--primary); display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 999px; background: #fff; border: 1px solid #dfe9e2; color: var(--gray-dark); font-weight: 600; font-size: .88rem; box-shadow: var(--shadow-sm); }
    .cat-pill i { color: var(--cat); }
    .cat-pill span { background: var(--gray-light); border-radius: 999px; padding: 0 8px; font-size: .75rem; }
    .cat-pill:hover { border-color: var(--cat); color: var(--dark); }
    .cat-pill.active { background: var(--cat); border-color: var(--cat); color: #fff; }
    .cat-pill.active i { color: #fff; }
    .cat-pill.active span { background: rgba(255,255,255,.25); }
  </style>
@endsection

@section('content')
<section class="catalog-hero">
  <div class="container">
    <h1><i class="fa-solid fa-basket-shopping"></i> Produits tracés</h1>
    <p>Tous les lots récoltés par les producteurs de Farm to Fork, avec leur exploitation d'origine, leur date de récolte et leur qualité.</p>
  </div>
</section>

<div class="container catalog-body">
  <div class="stat-grid">
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-boxes-stacked"></i></div><div><div class="stat__value">{{ $stats['products'] }}</div><div class="stat__label">Lots tracés</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-user-group"></i></div><div><div class="stat__value">{{ $stats['producers'] }}</div><div class="stat__label">Producteurs</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-wheat-awn"></i></div><div><div class="stat__value">{{ $stats['farms'] }}</div><div class="stat__label">Exploitations</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-warehouse"></i></div><div><div class="stat__value">{{ $stats['in_stock'] }}</div><div class="stat__label">Lots disponibles</div></div></div>
  </div>

  @if($user?->isProducer())
    <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="fa-solid fa-circle-info"></i> Vous voyez les produits de tous les producteurs. Vous ne pouvez modifier ou supprimer que les vôtres (encadrés en vert).</span>
      <span class="d-flex gap-2">
        <a href="{{ route('pro.harvests.create') }}" class="btn btn-success btn-sm"><i class="fa-solid fa-plus"></i> Nouveau produit</a>
        <a href="{{ route('pro.farms.index') }}" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-tractor"></i> Mon espace producteur</a>
      </span>
    </div>
  @elseif($user?->isAdmin())
    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="fa-solid fa-user-shield"></i> Mode administrateur : vous pouvez modifier ou supprimer tous les produits.</span>
      <a href="{{ route('backend.products.index') }}" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-gauge"></i> Gérer dans le back-office</a>
    </div>
  @endif

  {{-- Recherche intelligente en langage naturel --}}
  <form method="GET" action="{{ route('frontend.products') }}" class="smart-search" novalidate>
    <div class="smart-search__box">
      <i class="fa-solid fa-wand-magic-sparkles"></i>
      <input type="search" name="ask" value="{{ $ask }}" maxlength="150" placeholder="Décrivez ce que vous cherchez : « tomates bio de Nabeul en stock »…" aria-label="Recherche intelligente">
      <button type="submit">Rechercher</button>
    </div>
    @if($understood)
      <div class="smart-search__chips">
        <span><i class="fa-solid fa-brain"></i> Compris :</span>
        @foreach($understood as $chip)<em>{{ $chip }}</em>@endforeach
        <a href="{{ route('frontend.products') }}"><i class="fa-solid fa-xmark"></i> Effacer</a>
      </div>
    @elseif($ask)
      <div class="smart-search__chips"><span><i class="fa-solid fa-circle-info"></i> Aucun critère reconnu : essayez une catégorie, une région, « bio » ou « en stock ».</span></div>
    @else
      <div class="smart-search__examples">
        Exemples :
        @foreach(['olives bio de Sfax', 'légumes en stock qualité extra', 'fruits frais de Kairouan', 'dattes de Tozeur'] as $ex)
          <a href="{{ route('frontend.products', ['ask' => $ex]) }}">{{ $ex }}</a>
        @endforeach
      </div>
    @endif
    @error('ask')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
  </form>

  {{-- Navigation par catégorie --}}
  <nav class="cat-strip" aria-label="Catégories">
    <a href="{{ $catUrl(null) }}" class="cat-pill {{ empty($filters['category']) ? 'active' : '' }}"><i class="fa-solid fa-border-all"></i> Toutes</a>
    @foreach($categories as $c)
      <a href="{{ $catUrl($c->id) }}" class="cat-pill {{ (int) ($filters['category'] ?? 0) === $c->id ? 'active' : '' }}" style="--cat: {{ $c->color }}" title="{{ $c->description }}">
        <i class="fa-solid {{ $c->icon }}"></i> {{ $c->name }} <span>{{ $c->harvests_count }}</span>
      </a>
    @endforeach
  </nav>

  <form method="GET" action="{{ route('frontend.products') }}" class="p-card mb-4">
    <div class="filters catalog-filters">
      <div>
        <label class="form-label" for="q">Recherche</label>
        <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Produit, variété, ferme, code lot…">
      </div>
      <div>
        <label class="form-label" for="category">Catégorie</label>
        <select id="category" name="category" class="form-select">
          <option value="">Toutes</option>
          @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected((int) ($filters['category'] ?? 0) === $c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label" for="governorate">Région</label>
        <select id="governorate" name="governorate" class="form-select">
          <option value="">Toutes</option>
          @foreach(Farm::GOVERNORATES as $g)
            <option value="{{ $g }}" @selected(($filters['governorate'] ?? '') === $g)>{{ $g }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label" for="farming_type">Agriculture</label>
        <select id="farming_type" name="farming_type" class="form-select">
          <option value="">Toutes</option>
          @foreach(Farm::FARMING_TYPES as $k => $label)
            <option value="{{ $k }}" @selected(($filters['farming_type'] ?? '') === $k)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label" for="sort">Trier par</label>
        <select id="sort" name="sort" class="form-select">
          <option value="recent" @selected(request('sort') === 'recent')>Plus récents</option>
          <option value="oldest" @selected(request('sort') === 'oldest')>Plus anciens</option>
          <option value="name" @selected(request('sort') === 'name')>Nom (A→Z)</option>
          <option value="quantity" @selected(request('sort') === 'quantity')>Quantité</option>
        </select>
      </div>
      <div class="d-flex flex-column justify-content-end gap-1 small">
        <label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="availability" value="in_stock" @checked(($filters['availability'] ?? '') === 'in_stock')> Disponibles</label>
        @if($user?->isProducer())
          <label class="form-check mb-0"><input class="form-check-input" type="checkbox" name="mine" value="1" @checked(!empty($filters['mine']))> Mes produits</label>
        @endif
      </div>
      <div class="d-flex gap-2 align-items-end">
        <button class="btn btn-success" type="submit"><i class="fa-solid fa-filter"></i> Filtrer</button>
        @if(array_filter($filters) || request('sort'))
          <a href="{{ route('frontend.products') }}" class="btn btn-light" title="Réinitialiser"><i class="fa-solid fa-rotate-left"></i></a>
        @endif
      </div>
    </div>
  </form>

  @if($products->isEmpty())
    <div class="p-card empty-state">
      <i class="fa-solid fa-basket-shopping d-block"></i>
      <p>Aucun produit ne correspond à votre recherche.</p>
      <a href="{{ route('frontend.products') }}" class="btn btn-outline-success">Voir tous les produits</a>
    </div>
  @else
    <div class="product-grid">
      @foreach($products as $p)
        @include('frontend.partials.product-card', ['p' => $p, 'user' => $user])
      @endforeach
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $products->links('pagination::bootstrap-5') }}</div>
  @endif
</div>
@endsection
