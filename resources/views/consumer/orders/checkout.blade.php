@extends('frontend.layouts.app')

@section('title', 'Confirmation de commande | Nutritrace')

@section('content')
<div class="container py-5">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('frontend.home') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('consumer.catalog') }}">Catalogue</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('consumer.cart') }}">Mon Panier</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Confirmation</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold text-success">
                <i class="fa-solid fa-clipboard-check me-2"></i>Confirmation de commande
            </h1>
        </div>
    </div>

    <div class="row">
        <!-- Récapitulatif de commande -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-basket-shopping me-2"></i>Récapitulatif des articles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                    <th>Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cartItems as $item)
                                    <tr>
                                        <td>
                                            <h6 class="fw-bold mb-1">{{ $item->getProductName() }}</h6>
                                            <small class="text-muted">
                                                <i class="fa-solid fa-location-dot me-1"></i>
                                                {{ $item->distributorStock->getPointOfSaleName() }}, {{ $item->distributorStock->getCity() }}
                                            </small>
                                        </td>
                                        <td>{{ $item->getFormattedUnitPrice() }}</td>
                                        <td>{{ number_format($item->quantity, 2) }} {{ $item->distributorStock->productionLot->unit }}</td>
                                        <td class="fw-bold text-success">{{ $item->getFormattedSubtotal() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold text-success fs-5">
                                        {{ number_format($total, 2, ',', ' ') }} DT
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Informations de livraison -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-truck me-2"></i>Informations de livraison
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('consumer.orders.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nom complet *</label>
                                <input type="text" name="delivery_name" class="form-control" 
                                       value="{{ old('delivery_name', $deliveryData['delivery_name']) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Téléphone *</label>
                                <input type="tel" name="delivery_phone" class="form-control" 
                                       value="{{ old('delivery_phone', $deliveryData['delivery_phone']) }}" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Adresse *</label>
                                <input type="text" name="delivery_address" class="form-control" 
                                       value="{{ old('delivery_address', $deliveryData['delivery_address']) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Ville *</label>
                                <input type="text" name="delivery_city" class="form-control" 
                                       value="{{ old('delivery_city', $deliveryData['delivery_city']) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Gouvernorat *</label>
                                <input type="text" name="delivery_governorate" class="form-control" 
                                       value="{{ old('delivery_governorate', $deliveryData['delivery_governorate']) }}" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Notes de livraison (optionnel)</label>
                                <textarea name="delivery_notes" class="form-control" rows="3">{{ old('delivery_notes', $deliveryData['delivery_notes']) }}</textarea>
                            </div>
                        </div>

                        <!-- Mode de paiement -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mode de paiement *</label>
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="payment_cash" value="cash_on_delivery" checked>
                                        <label class="form-check-label" for="payment_cash">
                                            <i class="fa-solid fa-money-bill-wave me-2 text-success"></i>
                                            Paiement à la livraison (Cash on Delivery)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="payment_stripe" value="stripe">
                                        <label class="form-check-label" for="payment_stripe">
                                            <i class="fa-brands fa-stripe me-2 text-primary"></i>
                                            Carte bancaire (Stripe - Mode test)
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="fa-solid fa-info-circle me-1"></i>
                                        Le paiement Stripe est en mode test uniquement. Aucun argent réel ne sera débité.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('consumer.cart') }}" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-left me-2"></i>Retour au panier
                            </a>
                            <button type="submit" class="btn btn-success flex-grow-1 btn-lg">
                                <i class="fa-solid fa-check me-2"></i>Confirmer la commande
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Résumé latéral -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-receipt me-2"></i>Résumé
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Nombre d'articles:</span>
                        <strong>{{ $cartItems->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total:</span>
                        <strong>{{ number_format($total, 2, ',', ' ') }} DT</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Frais de livraison:</span>
                        <strong>Gratuit</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5">
                        <span class="fw-bold">Total:</span>
                        <span class="fw-bold text-success">{{ number_format($total, 2, ',', ' ') }} DT</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fa-solid fa-shield-halved me-2"></i>Sécurité
                    </h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Paiement à la livraison</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Stock vérifié en temps réel</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i>Transaction sécurisée</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
