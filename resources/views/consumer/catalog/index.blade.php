@extends('frontend.layouts.app')

@section('title', 'Catalogue | Produits Finis Disponibles')

@section('content')
<div class="container py-5">
    <!-- En-tête du catalogue -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('frontend.home') }}">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Catalogue</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold text-success">
                <i class="fa-solid fa-store me-2"></i>Catalogue des Produits Finis
            </h1>
            <p class="lead text-muted">
                Découvrez les produits transformés disponibles chez nos distributeurs agréés
            </p>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h3 class="display-6 fw-bold text-success">{{ $stats['total_products'] }}</h3>
                    <p class="mb-0 text-muted">Produits disponibles</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h3 class="display-6 fw-bold text-primary">{{ $stats['total_distributors'] }}</h3>
                    <p class="mb-0 text-muted">Distributeurs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h3 class="display-6 fw-bold text-info">{{ $stats['total_points_of_sale'] }}</h3>
                    <p class="mb-0 text-muted">Points de vente</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h3 class="display-6 fw-bold text-warning">
                        {{ $stats['avg_price'] ? number_format($stats['avg_price'], 2, ',', ' ') . ' DT' : 'N/A' }}
                    </h3>
                    <p class="mb-0 text-muted">Prix moyen</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('consumer.catalog') }}">
                <div class="row g-3">
                    <!-- Recherche -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Recherche</label>
                        <input type="text" name="q" class="form-control" placeholder="Nom produit, distributeur..." value="{{ $filters['q'] ?? '' }}">
                    </div>
                    <!-- Ville -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Ville</label>
                        <select name="city" class="form-select">
                            <option value="">Toutes les villes</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}" {{ ($filters['city'] ?? '') === $city ? 'selected' : '' }}>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Prix min -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Prix min (DT)</label>
                        <input type="number" name="min_price" class="form-control" placeholder="0" value="{{ $filters['min_price'] ?? '' }}" min="0" step="0.01">
                    </div>
                    <!-- Prix max -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Prix max (DT)</label>
                        <input type="number" name="max_price" class="form-control" placeholder="∞" value="{{ $filters['max_price'] ?? '' }}" min="0" step="0.01">
                    </div>
                    <!-- Tri -->
                    <div class="col-md-1">
                        <label class="form-label fw-semibold">Tri</label>
                        <select name="sort" class="form-select">
                            <option value="recent" {{ ($filters['sort'] ?? '') === 'recent' ? 'selected' : '' }}>Récents</option>
                            <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Prix ↑</option>
                            <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Prix ↓</option>
                            <option value="quantity" {{ ($filters['sort'] ?? '') === 'quantity' ? 'selected' : '' }}>Stock</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-search me-2"></i>Filtrer
                        </button>
                        <a href="{{ route('consumer.catalog') }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-rotate-left me-2"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des produits -->
    @if($stocks->count() > 0)
        <div class="row">
            @foreach($stocks as $stock)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                        @if($stock->productionLot->recipe)
                            <div class="card-header bg-light border-0">
                                <small class="text-muted">
                                    <i class="fa-solid fa-industry me-1"></i>
                                    {{ $stock->productionLot->processor->company_name ?? $stock->productionLot->processor->name }}
                                </small>
                            </div>
                        @endif
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">
                                {{ $stock->getProductName() }}
                            </h5>
                            <div class="mb-3">
                                <span class="badge bg-success fs-6">
                                    {{ $stock->getFormattedPrice() }}
                                </span>
                                <span class="badge bg-info fs-6 ms-2">
                                    {{ number_format($stock->quantity_available, 2) }} {{ $stock->productionLot->unit }} disponibles
                                </span>
                            </div>
                            <p class="card-text text-muted small mb-3">
                                <i class="fa-solid fa-location-dot me-1"></i>
                                {{ $stock->getPointOfSaleName() }}, {{ $stock->getCity() }}
                            </p>
                            <p class="card-text text-muted small mb-3">
                                <i class="fa-solid fa-truck me-1"></i>
                                {{ $stock->getDistributorName() }}
                            </p>
                            @if($stock->received_at)
                                <p class="card-text text-muted small mb-0">
                                    <i class="fa-solid fa-calendar me-1"></i>
                                    Reçu le {{ $stock->received_at->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>
                        <div class="card-footer bg-white border-0">
                            <div class="d-flex gap-2">
                                <a href="{{ route('consumer.catalog.show', $stock) }}" class="btn btn-outline-success flex-grow-1">
                                    <i class="fa-solid fa-eye me-2"></i>Détails
                                </a>
                                @auth
                                    @if(auth()->user()->isConsumer())
                                        <form method="POST" action="{{ route('consumer.cart.add', $stock) }}" class="flex-grow-1">
                                            @csrf
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fa-solid fa-cart-plus me-2"></i>Ajouter
                                            </button>
                                        </form>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="row">
            <div class="col-12">
                {{ $stocks->appends(request()->query())->links() }}
            </div>
        </div>
    @else
        <div class="alert alert-info text-center py-5">
            <i class="fa-solid fa-box-open fa-3x mb-3"></i>
            <h4>Aucun produit disponible</h4>
            <p class="mb-0">Aucun produit fini ne correspond actuellement à vos critères de recherche.</p>
        </div>
    @endif
</div>
@endsection
