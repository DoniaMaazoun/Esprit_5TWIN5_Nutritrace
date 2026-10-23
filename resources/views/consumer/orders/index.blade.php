@extends('frontend.layouts.app')

@section('title', 'Mes Commandes | Nutritrace')

@section('content')
<div class="container py-5">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('frontend.home') }}">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mes Commandes</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold text-success">
                <i class="fa-solid fa-list-check me-2"></i>Mes Commandes
            </h1>
        </div>
    </div>

    @if($orders->isEmpty())
        <div class="alert alert-info text-center py-5">
            <i class="fa-solid fa-box-open fa-3x mb-3"></i>
            <h4>Vous n'avez pas encore de commandes</h4>
            <p class="mb-0">Découvrez nos produits finis disponibles chez nos distributeurs.</p>
            <a href="{{ route('consumer.catalog') }}" class="btn btn-success mt-3">
                <i class="fa-solid fa-store me-2"></i>Parcourir le catalogue
            </a>
        </div>
    @else
        <!-- Liste des commandes -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Numéro</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>
                                        <span class="fw-bold">{{ $order->order_number }}</span>
                                    </td>
                                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ $order->getStatusBadgeColor() }}">
                                            {{ $order->getStatusLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $order->getPaymentMethodLabel() }}</small><br>
                                        <span class="badge bg-secondary">{{ $order->getPaymentStatusLabel() }}</span>
                                    </td>
                                    <td class="fw-bold text-success">{{ $order->getFormattedTotal() }}</td>
                                    <td>
                                        <a href="{{ route('consumer.orders.show', $order) }}" class="btn btn-sm btn-outline-success">
                                            <i class="fa-solid fa-eye"></i> Voir
                                        </a>
                                        @if($order->canBeCancelled())
                                            <form method="POST" action="{{ route('consumer.orders.cancel', $order) }}" class="d-inline ms-1">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        title="Annuler"
                                                        onclick="return confirm('Annuler cette commande ?')">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $orders->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
