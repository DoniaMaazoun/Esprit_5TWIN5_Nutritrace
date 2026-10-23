@extends('frontend.layouts.app')

@section('title', 'Mon Panier | Nutritrace')

@section('content')
<div class="container py-5">
    <!-- En-tête du panier -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('frontend.home') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('consumer.catalog') }}">Catalogue</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mon Panier</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold text-success">
                <i class="fa-solid fa-cart-shopping me-2"></i>Mon Panier
            </h1>
        </div>
    </div>

    @if($cartItems->isEmpty())
        <!-- Message d'erreur si redirection depuis checkout -->
        @if(session('error'))
            <div class="alert alert-danger text-center mb-4">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="alert alert-info text-center py-5">
            <i class="fa-solid fa-cart-arrow-down fa-3x mb-3"></i>
            <h4>Votre panier est vide</h4>
            <p class="mb-0">Découvrez nos produits finis disponibles chez nos distributeurs.</p>
            <a href="{{ route('consumer.catalog') }}" class="btn btn-success mt-3">
                <i class="fa-solid fa-store me-2"></i>Parcourir le catalogue
            </a>
        </div>
    @else
        <!-- Alertes pour articles indisponibles -->
        @if($unavailableItems->isNotEmpty())
            <div class="alert alert-warning mb-4">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <strong>Attention:</strong> Certains articles de votre panier ne sont plus disponibles en stock.
                Veuillez ajuster les quantités ou les supprimer.
            </div>
        @endif

        <!-- Tableau du panier -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cartItems as $item)
                                <tr class="{{ !$item->isQuantityAvailable() ? 'table-warning' : '' }}">
                                    <!-- Produit -->
                                    <td>
                                        <div>
                                            <h6 class="fw-bold mb-1">{{ $item->getProductName() }}</h6>
                                            <small class="text-muted">
                                                <i class="fa-solid fa-location-dot me-1"></i>
                                                {{ $item->distributorStock->getPointOfSaleName() }}, {{ $item->distributorStock->getCity() }}
                                            </small>
                                        </div>
                                        @if(!$item->isQuantityAvailable())
                                            <span class="badge bg-danger mt-1">Stock insuffisant</span>
                                        @endif
                                    </td>
                                    <!-- Prix unitaire -->
                                    <td>
                                        <span class="fw-semibold">{{ $item->getFormattedUnitPrice() }}</span>
                                    </td>
                                    <!-- Quantité -->
                                    <td>
                                        <form method="POST" action="{{ route('consumer.cart.update', $item) }}" class="d-flex align-items-center">
                                            @csrf
                                            @method('PUT')
                                            <div class="input-group input-group-sm" style="max-width: 150px;">
                                                <input type="number" name="quantity" 
                                                       class="form-control" 
                                                       value="{{ $item->quantity }}" 
                                                       min="0" 
                                                       max="{{ $item->getMaxQuantity() }}" 
                                                       step="0.01"
                                                       required>
                                                <button type="submit" class="btn btn-outline-secondary">
                                                    <i class="fa-solid fa-rotate"></i>
                                                </button>
                                            </div>
                                        </form>
                                        <small class="text-muted">Max: {{ $item->getMaxQuantity() }}</small>
                                    </td>
                                    <!-- Sous-total -->
                                    <td>
                                        <span class="fw-bold text-success">{{ $item->getFormattedSubtotal() }}</span>
                                    </td>
                                    <!-- Actions -->
                                    <td>
                                        <form method="POST" action="{{ route('consumer.cart.remove', $item) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    title="Supprimer"
                                                    onclick="return confirm('Supprimer cet article du panier ?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total du panier:</td>
                                <td colspan="2">
                                    <span class="display-6 fw-bold text-success">
                                        {{ number_format($total, 2, ',', ' ') }} DT
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Actions du panier -->
        <div class="row">
            <div class="col-md-6">
                <form method="POST" action="{{ route('consumer.cart.clear') }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" 
                            onclick="return confirm('Vider le panier ? Tous les articles seront supprimés.')">
                        <i class="fa-solid fa-trash-can me-2"></i>Vider le panier
                    </button>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <a href="{{ route('consumer.catalog') }}" class="btn btn-outline-secondary me-2">
                    <i class="fa-solid fa-arrow-left me-2"></i>Continuer mes achats
                </a>
                <a href="{{ route('consumer.orders.checkout') }}" class="btn btn-success">
                    <i class="fa-solid fa-credit-card me-2"></i>Passer la commande
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
