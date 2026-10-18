@extends('backend.layouts.app')

@section('title', 'Expéditions - Farm to Fork Admin')

@section('content')
<div class="row">
  <div class="col-md-12">
    <h2 class="page-title mb-4"><i class="mdi mdi-map-marker-path"></i> Supervision des expéditions</h2>
  </div>
</div>

@include('backend.pages.logistics.partials.alerts')

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <form method="GET" action="{{ route('backend.logistics.shipments.index') }}" class="form-inline mb-3">
          <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach(\App\Models\Shipment::STATUSES as $value => $label)
              <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </form>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Référence</th>
                <th>Distributeur</th>
                <th>Produit</th>
                <th>Destination</th>
                <th>Statut</th>
                <th class="text-right">Distance (km)</th>
                <th class="text-right">CO2 (kg)</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($shipments as $shipment)
                <tr>
                  <td><strong>{{ $shipment->reference }}</strong></td>
                  <td>{{ $shipment->user->company_name ?? $shipment->user->name }}</td>
                  <td>{{ $shipment->product_name }}</td>
                  <td>{{ $shipment->pointOfSale?->name ?? '—' }}</td>
                  <td><span class="badge badge-info">{{ $shipment->getStatusLabel() }}</span></td>
                  <td class="text-right">{{ number_format($shipment->total_distance_km, 1, ',', ' ') }}</td>
                  <td class="text-right">{{ number_format($shipment->total_co2_kg, 2, ',', ' ') }}</td>
                  <td class="text-right text-nowrap">
                    <a href="{{ route('backend.logistics.shipments.show', $shipment) }}" class="btn btn-sm btn-primary">
                      <i class="mdi mdi-eye"></i> Voir
                    </a>
                    <form method="POST" action="{{ route('backend.logistics.shipments.destroy', $shipment) }}" style="display: inline;">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette expédition ?');">
                        <i class="mdi mdi-delete"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted">Aucune expédition.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        {{ $shipments->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>
</div>
@endsection
