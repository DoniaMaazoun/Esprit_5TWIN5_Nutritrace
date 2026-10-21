@extends('pro.layouts.processor')

@section('title', 'Mes Recettes | Farm to Fork')

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
    <h1><i class="fa-solid fa-gears"></i> Mes Recettes</h1>
    <p>Gérez vos formules de transformation avec leurs ingrédients requis. Créez, modifiez et organisez vos recettes.</p>
  </div>
</section>

<div class="container catalog-body">
  <div class="stat-grid">
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-book"></i></div><div><div class="stat__value">{{ $stats['total'] }}</div><div class="stat__label">Recettes</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-check-circle"></i></div><div><div class="stat__value">{{ $stats['active'] }}</div><div class="stat__label">Actives</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-pause-circle"></i></div><div><div class="stat__value">{{ $stats['inactive'] }}</div><div class="stat__label">Inactives</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-cubes"></i></div><div><div class="stat__value">{{ $recipes->total() }}</div><div class="stat__label">Affichées</div></div></div>
  </div>

  {{-- Barre de recherche intelligente --}}
  <form method="GET" action="{{ route('pro.processor.recipes.index') }}" class="smart-search" novalidate>
    <div class="smart-search__box">
      <i class="fa-solid fa-wand-magic-sparkles"></i>
      <input type="search" name="q" value="{{ $search ?? '' }}" maxlength="150" placeholder="Cherchez une recette, ingrédient, rendement…" aria-label="Recherche intelligente">
      <button type="submit">Chercher</button>
    </div>
    @if(!empty($search))
      <div class="smart-search__chips">
        <span><i class="fa-solid fa-search"></i> Résultats pour :</span>
        <em>{{ $search }}</em>
        <a href="{{ route('pro.processor.recipes.index') }}"><i class="fa-solid fa-xmark"></i> Effacer</a>
      </div>
    @else
      <div class="smart-search__examples">
        Exemples :
        <a href="{{ route('pro.processor.recipes.index', ['q' => 'sauce']) }}">sauce tomate</a>
        <a href="{{ route('pro.processor.recipes.index', ['q' => 'purée']) }}">purée</a>
        <a href="{{ route('pro.processor.recipes.index', ['q' => '1L']) }}">1L</a>
      </div>
    @endif
  </form>

  <form method="GET" action="{{ route('pro.processor.recipes.index') }}" class="p-card mb-4">
    <div class="filters catalog-filters">
      <div>
        <label class="form-label" for="q-filter">Recherche</label>
        <input type="search" id="q-filter" name="q" value="{{ $search ?? '' }}" class="form-control" placeholder="Nom, description…">
      </div>
      <div>
        <label class="form-label" for="sort">Trier par</label>
        <select id="sort" name="sort" class="form-select">
          <option value="created_at" @selected(request('sort') === 'created_at' || !request('sort'))>Plus récentes</option>
          <option value="name" @selected(request('sort') === 'name')>Nom (A-Z)</option>
          <option value="yield_quantity" @selected(request('sort') === 'yield_quantity')>Rendement</option>
        </select>
      </div>
      <div style="display: flex; flex-direction: column; justify-content: flex-end; gap: 6px; font-size: 0.88rem;">
        <label class="form-check mb-0">
          <input class="form-check-input" type="checkbox" name="active_only" value="1" @checked(request('active_only') === '1')>
          Actives seulement
        </label>
      </div>
      <div class="d-flex gap-2 align-items-end">
        <button class="btn btn-success" type="submit"><i class="fa-solid fa-filter"></i> Filtrer</button>
        @if(!empty($search) || request('sort') !== 'created_at' || request('active_only'))
          <a href="{{ route('pro.processor.recipes.index') }}" class="btn btn-light" title="Réinitialiser"><i class="fa-solid fa-rotate-left"></i></a>
        @endif
      </div>
    </div>
  </form>

  @if($recipes->isEmpty())
    <div class="p-card empty-state">
      <i class="fa-solid fa-gears d-block"></i>
      @if(!empty($search))
        <p>Aucune recette ne correspond à votre recherche.</p>
      @else
        <p>Aucune recette créée pour le moment.</p>
      @endif
      @if(!empty($search))
        <a href="{{ route('pro.processor.recipes.index') }}" class="btn btn-outline-success">
          <i class="fa-solid fa-plus"></i> Voir toutes mes recettes
        </a>
      @else
        <a href="{{ route('pro.processor.recipes.create') }}" class="btn btn-outline-success">
          <i class="fa-solid fa-plus"></i> Créer ma première recette
        </a>
      @endif
    </div>
  @else
    <div class="recipe-grid">
      @foreach($recipes as $recipe)
        @include('pro.processor.partials.recipe-card', ['recipe' => $recipe])
      @endforeach
    </div>
  @endif

  @if(!$recipes->isEmpty())
    <div class="mt-4 d-flex justify-content-center">
      {{ $recipes->links('pagination::bootstrap-5') }}
    </div>
  @endif

  <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2" style="margin-top: 20px;">
    <span><i class="fa-solid fa-circle-info"></i> Vous gérez toutes vos recettes de transformation. Créez des recettes avec les matières brutes que vous avez reçues.</span>
    <a href="{{ route('pro.processor.recipes.create') }}" class="btn btn-success btn-sm"><i class="fa-solid fa-plus"></i> Nouvelle Recette</a>
  </div>
</div>

@endsection
