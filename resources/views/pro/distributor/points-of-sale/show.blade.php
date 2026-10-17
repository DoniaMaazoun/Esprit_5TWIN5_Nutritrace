@extends('frontend.layouts.app')

@section('title', $pointOfSale->name . ' | Farm to Fork')

@section('extra-css')
  @include('pro.distributor.partials.styles')
@endsection

@section('content')
<div class="distributor-page">
  @include('pro.distributor.partials.layout-start')

  <div class="card distributor-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
      <span><i class="fa-solid fa-store"></i> {{ $pointOfSale->name }}</span>
      <a href="{{ route('pro.distributor.points-of-sale.edit', $pointOfSale) }}" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-pen"></i> Modifier
      </a>
    </div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3">Type</dt>
        <dd class="col-sm-9">{{ $pointOfSale->getTypeLabel() }}</dd>
        <dt class="col-sm-3">Adresse</dt>
        <dd class="col-sm-9">{{ $pointOfSale->address }}, {{ $pointOfSale->city }}</dd>
        @if($pointOfSale->latitude && $pointOfSale->longitude)
          <dt class="col-sm-3">Coordonnées GPS</dt>
          <dd class="col-sm-9">{{ $pointOfSale->latitude }}, {{ $pointOfSale->longitude }}</dd>
        @endif
      </dl>
    </div>
  </div>

  <div class="card distributor-card">
    <div class="card-header py-3"><i class="fa-solid fa-route"></i> Expéditions reçues</div>
    <div class="card-body">
      @if($shipments->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Référence</th>
                <th>Produit</th>
                <th>Statut</th>
                <th class="text-end">CO2 (kg)</th>
              </tr>
            </thead>
            <tbody>
              @foreach($shipments as $shipment)
                <tr>
                  <td><a href="{{ route('pro.distributor.shipments.show', $shipment) }}">{{ $shipment->reference }}</a></td>
                  <td>{{ $shipment->product_name }}</td>
                  <td>@include('pro.distributor.partials.status-badge')</td>
                  <td class="text-end">{{ number_format($shipment->total_co2_kg, 2, ',', ' ') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        {{ $shipments->links('pagination::bootstrap-5') }}
      @else
        <div class="alert alert-info mb-0">Aucune expédition vers ce point de vente.</div>
      @endif
    </div>
  </div>
</div>
</div>
@endsection
