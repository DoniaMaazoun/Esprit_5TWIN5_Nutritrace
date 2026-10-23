@extends('frontend.layouts.app')

@section('title', 'Paiement annulé | Nutritrace')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fa-solid fa-circle-xmark text-danger fa-5x"></i>
                    </div>
                    <h1 class="display-5 fw-bold text-danger mb-3">Paiement annulé</h1>
                    <p class="lead text-muted mb-4">
                        Le paiement pour la commande #{{ $order->order_number }} a été annulé ou a échoué.
                    </p>
                    <div class="alert alert-warning d-inline-block">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        Les réservations de stock ont été libérées. Vous pouvez réessayer ultérieurement.
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('consumer.orders.show', $order) }}" class="btn btn-outline-primary btn-lg">
                            <i class="fa-solid fa-eye me-2"></i>Voir la commande
                        </a>
                        <a href="{{ route('consumer.catalog') }}" class="btn btn-success btn-lg ms-2">
                            <i class="fa-solid fa-store me-2"></i>Retour au catalogue
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
