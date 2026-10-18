@extends('frontend.layouts.app')

@section('title', 'Expédition ' . $shipment->reference . ' | Farm to Fork')

@section('extra-css')
  @include('pro.distributor.partials.styles')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endsection

@section('content')
<div class="distributor-page">
  @include('pro.distributor.partials.layout-start')

  <!-- Résumé de l'expédition -->
  <div class="card distributor-card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
      <span><i class="fa-solid fa-route"></i> Expédition {{ $shipment->reference }} @include('pro.distributor.partials.status-badge')</span>
      <div class="d-flex gap-2">
        <a href="{{ route('pro.distributor.shipments.edit', $shipment) }}" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-pen"></i> Modifier
        </a>
        <form method="POST" action="{{ route('pro.distributor.shipments.destroy', $shipment) }}">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer cette expédition et toutes ses étapes ?');">
            <i class="fa-solid fa-trash"></i> Supprimer
          </button>
        </form>
      </div>
    </div>
    <div class="card-body">
      <div class="row text-center mb-4">
        <div class="col-md-4">
          <div class="stat-value">{{ number_format($shipment->weight_kg, 2, ',', ' ') }} kg</div>
          <div class="text-muted">Poids transporté</div>
        </div>
        <div class="col-md-4">
          <div class="stat-value">{{ number_format($shipment->total_distance_km, 1, ',', ' ') }} km</div>
          <div class="text-muted">Distance totale</div>
        </div>
        <div class="col-md-4">
          <div class="stat-value text-success">{{ number_format($shipment->total_co2_kg, 2, ',', ' ') }} kg CO2e</div>
          <div class="text-muted">Empreinte carbone</div>
        </div>
      </div>
      <dl class="row mb-0">
        <dt class="col-sm-3">Produit</dt>
        <dd class="col-sm-9">{{ $shipment->product_name }}@if($shipment->harvest) · Lot <a href="{{ $shipment->harvest->traceabilityUrl() }}" target="_blank">{{ $shipment->harvest->batch_code }}</a> ({{ $shipment->harvest->farm->name }})@endif</dd>
        <dt class="col-sm-3">Départ</dt>
        <dd class="col-sm-9">{{ $shipment->origin_address }}@if($shipment->departed_at) · {{ $shipment->departed_at->format('d/m/Y H:i') }}@endif</dd>
        <dt class="col-sm-3">Destination</dt>
        <dd class="col-sm-9">
          @if($shipment->pointOfSale)
            <a href="{{ route('pro.distributor.points-of-sale.show', $shipment->pointOfSale) }}">{{ $shipment->pointOfSale->name }}</a>, {{ $shipment->pointOfSale->city }}
          @else
            —
          @endif
          @if($shipment->delivered_at) · livrée le {{ $shipment->delivered_at->format('d/m/Y H:i') }}@endif
        </dd>
      </dl>
    </div>
  </div>

  @include('pro.distributor.shipments._map')

  <!-- Étapes de transport -->
  <div class="card distributor-card mb-4">
    <div class="card-header py-3"><i class="fa-solid fa-truck-fast"></i> Étapes de transport</div>
    <div class="card-body">
      @if($shipment->legs->count() > 0)
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Mode</th>
                <th>Trajet</th>
                <th class="text-end">Distance (km)</th>
                <th class="text-end">Poids (t)</th>
                <th class="text-end">Facteur (kg/t.km)</th>
                <th class="text-end">CO2 (kg)</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($shipment->legs as $leg)
                <tr>
                  <td>{{ $leg->position }}</td>
                  <td>{{ $leg->transportMode->name }}</td>
                  <td>{{ $leg->from_location }} <i class="fa-solid fa-arrow-right text-muted"></i> {{ $leg->to_location }}</td>
                  <td class="text-end">{{ number_format($leg->distance_km, 1, ',', ' ') }}</td>
                  <td class="text-end">{{ number_format($leg->weight_kg / 1000, 3, ',', ' ') }}</td>
                  <td class="text-end">{{ number_format($leg->emission_factor, 4, ',', ' ') }}</td>
                  <td class="text-end"><span class="badge co2-badge">{{ number_format($leg->co2_kg, 2, ',', ' ') }}</span></td>
                  <td class="text-end text-nowrap">
                    <a href="{{ route('pro.distributor.shipments.legs.edit', [$shipment, $leg]) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                      <i class="fa-solid fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('pro.distributor.shipments.legs.destroy', [$shipment, $leg]) }}" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer cette étape ?');">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="alert alert-info">Aucune étape : ajoutez le premier trajet ci-dessous.</div>
      @endif

      <h6 class="mt-4 mb-3">Ajouter une étape</h6>
      <form method="POST" action="{{ route('pro.distributor.shipments.legs.store', $shipment) }}">
        @csrf
        @include('pro.distributor.shipments._recommendation')
        @include('pro.distributor.legs._form', ['leg' => new \App\Models\ShipmentLeg()])
        <p class="form-text mt-2 mb-0">CO2 (kg) = distance (km) × poids (tonnes) × facteur d'émission du mode de transport (ADEME).</p>
        <button type="submit" class="btn btn-success mt-3"><i class="fa-solid fa-plus"></i> Ajouter l'étape</button>
      </form>
    </div>
  </div>

  <!-- Journal de traçabilité -->
  <div class="card distributor-card">
    <div class="card-header py-3"><i class="fa-solid fa-clipboard-list"></i> Journal de traçabilité</div>
    <div class="card-body">
      @if($shipment->events->count() > 0)
        <ul class="list-group mb-4">
          @foreach($shipment->events as $event)
            <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
              <div>
                <strong>{{ $event->getTypeLabel() }}</strong>
                <span class="text-muted">· {{ $event->occurred_at->format('d/m/Y H:i') }}@if($event->location) · {{ $event->location }}@endif</span>
                @if($event->note)<div class="small">{{ $event->note }}</div>@endif
              </div>
              <div class="text-nowrap">
                <a href="{{ route('pro.distributor.shipments.events.edit', [$shipment, $event]) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                  <i class="fa-solid fa-pen"></i>
                </a>
                <form method="POST" action="{{ route('pro.distributor.shipments.events.destroy', [$shipment, $event]) }}" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer cet événement ?');">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              </div>
            </li>
          @endforeach
        </ul>
      @else
        <div class="alert alert-info">Aucun événement enregistré.</div>
      @endif

      <h6 class="mb-3">Ajouter un événement</h6>
      <form method="POST" action="{{ route('pro.distributor.shipments.events.store', $shipment) }}">
        @csrf
        @include('pro.distributor.events._form', ['event' => new \App\Models\ShipmentEvent()])
        <button type="submit" class="btn btn-success mt-3"><i class="fa-solid fa-plus"></i> Ajouter l'événement</button>
      </form>
    </div>
  </div>
</div>
</div>
@endsection
