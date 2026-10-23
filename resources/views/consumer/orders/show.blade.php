@extends('frontend.layouts.app')

@section('title', 'Commande ' . $order->order_number . ' | Nutritrace')

@section('content')
<div class="container py-5">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('frontend.home') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('consumer.orders.index') }}">Mes Commandes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $order->order_number }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5 fw-bold text-success">
                        <i class="fa-solid fa-receipt me-2"></i>Commande #{{ $order->order_number }}
                    </h1>
                    <p class="text-muted mb-0">Passée le {{ $order->created_at->format('d/m/Y à H:i') }}</p>
                </div>
                @if($order->canBeCancelled())
                    <form method="POST" action="{{ route('consumer.orders.cancel', $order) }}">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-outline-danger" 
                                onclick="return confirm('Annuler cette commande ? Le stock sera remis en place.')">
                            <i class="fa-solid fa-xmark me-2"></i>Annuler la commande
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Détails de la commande -->
        <div class="col-lg-8 mb-4">
            <!-- Statuts -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Statut de la commande</label>
                            <div>
                                <span class="badge {{ $order->getStatusBadgeColor() }} fs-6">
                                    {{ $order->getStatusLabel() }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Statut du paiement</label>
                            <div>
                                <span class="badge bg-secondary fs-6">{{ $order->getPaymentStatusLabel() }}</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Mode de paiement</label>
                            <div class="fw-semibold">{{ $order->getPaymentMethodLabel() }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Montant total</label>
                            <div class="fw-bold text-success fs-5">{{ $order->getFormattedTotal() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Articles commandés -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-basket-shopping me-2"></i>Articles commandés
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th>Point de vente</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                    <th>Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderItems as $item)
                                    <tr>
                                        <td>
                                            <h6 class="fw-bold mb-1">{{ $item->product_name }}</h6>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $item->point_of_sale_name }}</small>
                                        </td>
                                        <td>{{ $item->getFormattedUnitPrice() }}</td>
                                        <td>{{ number_format($item->quantity, 2) }}</td>
                                        <td class="fw-bold text-success">{{ $item->getFormattedSubtotal() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold text-success fs-5">{{ $order->getFormattedTotal() }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations de livraison -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-truck me-2"></i>Informations de livraison
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Nom</label>
                        <div class="fw-semibold">{{ $order->delivery_name }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Téléphone</label>
                        <div class="fw-semibold">{{ $order->delivery_phone }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Adresse</label>
                        <div class="fw-semibold">{{ $order->delivery_address }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Ville</label>
                        <div class="fw-semibold">{{ $order->delivery_city }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Gouvernorat</label>
                        <div class="fw-semibold">{{ $order->delivery_governorate }}</div>
                    </div>
                    @if($order->delivery_notes)
                        <div class="mb-0">
                            <label class="text-muted small">Notes</label>
                            <div class="fw-semibold">{{ $order->delivery_notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <a href="{{ route('consumer.orders.index') }}" class="btn btn-outline-success w-100">
                        <i class="fa-solid fa-arrow-left me-2"></i>Retour à mes commandes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
