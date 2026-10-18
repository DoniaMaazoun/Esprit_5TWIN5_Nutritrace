@extends('frontend.layouts.app')

@section('title', 'Expéditions | Farm to Fork')

@section('extra-css')
  @include('pro.distributor.partials.styles')
@endsection

@section('content')
<div class="distributor-page">
  @include('pro.distributor.partials.layout-start')

  <div class="card distributor-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
      <span><i class="fa-solid fa-route"></i> Mes expéditions</span>
      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('pro.distributor.shipments.index') }}">
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach(\App\Models\Shipment::STATUSES as $value => $label)
              <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </form>
        <a href="{{ route('pro.distributor.shipments.create') }}" class="btn btn-success btn-sm text-nowrap">
          <i class="fa-solid fa-plus"></i> Nouvelle expédition
        </a>
      </div>
    </div>
    <div class="card-body">
      @if($shipments->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Référence</th>
                <th>Produit</th>
                <th>Poids (kg)</th>
                <th>Destination</th>
                <th>Statut</th>
                <th class="text-end">Distance (km)</th>
                <th class="text-end">CO2 (kg)</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($shipments as $shipment)
                <tr>
                  <td><strong>{{ $shipment->reference }}</strong></td>
                  <td>{{ $shipment->product_name }}</td>
                  <td>{{ number_format($shipment->weight_kg, 2, ',', ' ') }}</td>
                  <td>{{ $shipment->pointOfSale?->name ?? '—' }}</td>
                  <td>@include('pro.distributor.partials.status-badge')</td>
                  <td class="text-end">{{ number_format($shipment->total_distance_km, 1, ',', ' ') }}</td>
                  <td class="text-end"><span class="badge co2-badge">{{ number_format($shipment->total_co2_kg, 2, ',', ' ') }}</span></td>
                  <td class="text-end text-nowrap">
                    <a href="{{ route('pro.distributor.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-secondary" title="Voir">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="{{ route('pro.distributor.shipments.edit', $shipment) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                      <i class="fa-solid fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('pro.distributor.shipments.destroy', $shipment) }}" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"
                              onclick="return confirm('Supprimer cette expédition et toutes ses étapes ?');">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        {{ $shipments->links('pagination::bootstrap-5') }}
      @else
        <div class="alert alert-info mb-0">
          <i class="fa-solid fa-circle-info"></i> Aucune expédition pour le moment.
        </div>
      @endif
    </div>
  </div>
</div>
</div>
@endsection
