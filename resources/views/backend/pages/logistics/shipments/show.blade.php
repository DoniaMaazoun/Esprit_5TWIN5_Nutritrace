@extends('backend.layouts.app')

@section('title', 'Expédition ' . $shipment->reference . ' - Farm to Fork Admin')

@section('content')
<div class="row">
  <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0"><i class="mdi mdi-map-marker-path"></i> Expédition {{ $shipment->reference }}</h2>
    <a href="{{ route('backend.logistics.shipments.index') }}" class="btn btn-light btn-sm"><i class="mdi mdi-arrow-left"></i> Retour</a>
  </div>
</div>

<div class="row">
  <div class="col-md-5 grid-margin">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Informations</h5>
        <dl class="row mb-0">
          <dt class="col-sm-5">Distributeur</dt>
          <dd class="col-sm-7">{{ $shipment->user->company_name ?? $shipment->user->name }}</dd>
          <dt class="col-sm-5">Produit</dt>
          <dd class="col-sm-7">{{ $shipment->product_name }}@if($shipment->batch_id) (lot n° {{ $shipment->batch_id }})@endif</dd>
          <dt class="col-sm-5">Poids</dt>
          <dd class="col-sm-7">{{ number_format($shipment->weight_kg, 2, ',', ' ') }} kg</dd>
          <dt class="col-sm-5">Départ</dt>
          <dd class="col-sm-7">{{ $shipment->origin_address }}</dd>
          <dt class="col-sm-5">Destination</dt>
          <dd class="col-sm-7">{{ $shipment->pointOfSale?->name ?? '—' }}</dd>
          <dt class="col-sm-5">Statut</dt>
          <dd class="col-sm-7"><span class="badge badge-info">{{ $shipment->getStatusLabel() }}</span></dd>
          <dt class="col-sm-5">Distance totale</dt>
          <dd class="col-sm-7">{{ number_format($shipment->total_distance_km, 1, ',', ' ') }} km</dd>
          <dt class="col-sm-5">Empreinte carbone</dt>
          <dd class="col-sm-7"><strong>{{ number_format($shipment->total_co2_kg, 2, ',', ' ') }} kg CO2e</strong></dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-md-7 grid-margin">
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Étapes de transport</h5>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Mode</th>
                <th>Trajet</th>
                <th class="text-right">km</th>
                <th class="text-right">CO2 (kg)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($shipment->legs as $leg)
                <tr>
                  <td>{{ $leg->position }}</td>
                  <td>{{ $leg->transportMode->name }}</td>
                  <td>{{ $leg->from_location }} → {{ $leg->to_location }}</td>
                  <td class="text-right">{{ number_format($leg->distance_km, 1, ',', ' ') }}</td>
                  <td class="text-right">{{ number_format($leg->co2_kg, 2, ',', ' ') }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-muted text-center">Aucune étape.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Journal de traçabilité</h5>
        <ul class="list-unstyled mb-0">
          @forelse($shipment->events as $event)
            <li class="mb-2">
              <strong>{{ $event->getTypeLabel() }}</strong>
              <span class="text-muted">· {{ $event->occurred_at->format('d/m/Y H:i') }}@if($event->location) · {{ $event->location }}@endif</span>
              @if($event->note)<br><small>{{ $event->note }}</small>@endif
            </li>
          @empty
            <li class="text-muted">Aucun événement.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
