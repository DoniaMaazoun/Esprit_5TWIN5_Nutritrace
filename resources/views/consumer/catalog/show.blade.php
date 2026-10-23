@extends('frontend.layouts.app')

@section('title', $stock->getProductName() . ' | Détails')

@section('content')
<div class="container py-5">
    <!-- Breadcrumb -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('frontend.home') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('consumer.catalog') }}">Catalogue</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $stock->getProductName() }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Détail du produit -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="display-5 fw-bold text-success mb-4">
                        {{ $stock->getProductName() }}
                    </h1>

                    <!-- Informations principales -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small">Prix unitaire</label>
                                <h3 class="fw-bold text-success">{{ $stock->getFormattedPrice() }}</h3>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Quantité disponible</label>
                                <h4 class="fw-bold">
                                    <span class="badge bg-info fs-5">
                                        {{ number_format($stock->quantity_available, 2) }} {{ $stock->productionLot->unit }}
                                    </span>
                                </h4>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Statut</label>
                                <div>
                                    <span class="badge bg-{{ $stock->isAvailable() ? 'success' : 'secondary' }}">
                                        {{ $stock->getStatusLabel() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small">Date de production</label>
                                <p class="mb-0 fw-semibold">{{ $stock->productionLot->production_date->format('d/m/Y') }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Date de péremption</label>
                                <p class="mb-0 fw-semibold {{ $stock->productionLot->isExpired() ? 'text-danger' : '' }}">
                                    {{ $stock->productionLot->expiry_date->format('d/m/Y') }}
                                    @if($stock->productionLot->isExpired())
                                        <span class="badge bg-danger ms-2">Expiré</span>
                                    @elseif($stock->productionLot->isCloseToExpiry())
                                        <span class="badge bg-warning ms-2">Proche expiration</span>
                                    @endif
                                </p>
                            </div>
                            @if($stock->received_at)
                                <div class="mb-3">
                                    <label class="text-muted small">Date de réception</label>
                                    <p class="mb-0 fw-semibold">{{ $stock->received_at->format('d/m/Y') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Description de la recette -->
                    @if($stock->productionLot->recipe)
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">
                                <i class="fa-solid fa-scroll me-2"></i>Description
                            </h5>
                            <p class="text-muted">{{ $stock->productionLot->recipe->description ?? 'Aucune description disponible' }}</p>
                        </div>

                        <!-- Ingrédients -->
                        @if($stock->productionLot->recipe->items->count() > 0)
                            <div class="mb-4">
                                <h5 class="fw-bold mb-3">
                                    <i class="fa-solid fa-list me-2"></i>Ingrédients
                                </h5>
                                <ul class="list-group">
                                    @foreach($stock->productionLot->recipe->items as $item)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>{{ $item->raw_material_name }}</span>
                                            <span class="badge bg-secondary">{{ $item->getQuantityDisplay() }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    <!-- Notes de production -->
                    @if($stock->productionLot->production_notes)
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">
                                <i class="fa-solid fa-note-sticky me-2"></i>Notes de production
                            </h5>
                            <p class="text-muted">{{ $stock->productionLot->production_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Point de vente -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="fw-bold mb-0">
                        <i class="fa-solid fa-store me-2"></i>Point de vente
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-2">{{ $stock->getPointOfSaleName() }}</h5>
                    <p class="text-muted mb-2">
                        <i class="fa-solid fa-location-dot me-1"></i>
                        {{ $stock->pointOfSale->address }}, {{ $stock->getCity() }}
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fa-solid fa-building me-1"></i>
                        {{ $stock->getDistributorName() }}
                    </p>
                    @if($stock->pointOfSale->type)
                        <div class="mt-2">
                            <span class="badge bg-secondary">{{ $stock->pointOfSale->getTypeLabel() }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Traçabilité -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="fw-bold mb-0">
                        <i class="fa-solid fa-route me-2"></i>Traçabilité
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Transformateur -->
                    <div class="mb-3">
                        <label class="text-muted small">Transformateur</label>
                        <p class="mb-0 fw-semibold">
                            {{ $stock->productionLot->processor->company_name ?? $stock->productionLot->processor->name }}
                        </p>
                    </div>

                    <!-- Recette -->
                    @if($stock->productionLot->recipe)
                        <div class="mb-3">
                            <label class="text-muted small">Recette</label>
                            <p class="mb-0 fw-semibold">{{ $stock->productionLot->recipe->name }}</p>
                            <p class="text-muted small mb-0">
                                Rendement: {{ $stock->productionLot->recipe->getYieldDisplay() }}
                            </p>
                        </div>
                    @endif

                    <!-- Statut du lot -->
                    <div class="mb-0">
                        <label class="text-muted small">Statut du lot</label>
                        <div>
                            <span class="badge" style="background-color: {{ $stock->productionLot->getStatusBadgeColor() }}">
                                {{ $stock->productionLot->getStatusLabel() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @auth
                        @if(auth()->user()->isConsumer())
                            <form method="POST" action="{{ route('consumer.cart.add', $stock) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Quantité</label>
                                    <div class="input-group">
                                        <input type="number" name="quantity" class="form-control" 
                                               value="1" min="1" max="{{ $stock->quantity_available }}" step="0.01" required>
                                        <span class="input-group-text">{{ $stock->productionLot->unit }}</span>
                                    </div>
                                    <small class="text-muted">Maximum disponible: {{ $stock->quantity_available }} {{ $stock->productionLot->unit }}</small>
                                </div>
                                <button type="submit" class="btn btn-success w-100 btn-lg">
                                    <i class="fa-solid fa-cart-plus me-2"></i>Ajouter au panier
                                </button>
                            </form>
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="fa-solid fa-info-circle me-2"></i>
                                Connectez-vous en tant que consommateur pour ajouter ce produit au panier.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <a href="{{ route('login') }}" class="alert-link">Connectez-vous</a> pour ajouter ce produit au panier.
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
