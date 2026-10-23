@extends('frontend.layouts.app')

@section('title', 'Paiement réussi | Nutritrace')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fa-solid fa-circle-check text-success fa-5x"></i>
                    </div>
                    <h1 class="display-5 fw-bold text-success mb-3">Paiement réussi !</h1>
                    <p class="lead text-muted mb-4">
                        Votre commande #{{ $order->order_number }} a été confirmée.
                    </p>
                    <div class="alert alert-info d-inline-block">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        Le paiement a été traité avec succès via Stripe (mode test).
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('consumer.orders.show', $order) }}" class="btn btn-success btn-lg">
                            <i class="fa-solid fa-eye me-2"></i>Voir ma commande
                        </a>
                        <a href="{{ route('consumer.orders.index') }}" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fa-solid fa-list me-2"></i>Mes commandes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
