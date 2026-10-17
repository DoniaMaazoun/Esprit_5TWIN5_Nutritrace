@extends('frontend.layouts.app')

@section('title', 'Points de vente | Farm to Fork')

@section('extra-css')
  @include('pro.distributor.partials.styles')
@endsection

@section('content')
<div class="distributor-page">
  @include('pro.distributor.partials.layout-start')

  <div class="card distributor-card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
      <span><i class="fa-solid fa-store"></i> Mes points de vente</span>
      <a href="{{ route('pro.distributor.points-of-sale.create') }}" class="btn btn-success btn-sm">
        <i class="fa-solid fa-plus"></i> Nouveau point de vente
      </a>
    </div>
    <div class="card-body">
      @if($pointsOfSale->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Type</th>
                <th>Adresse</th>
                <th>Ville</th>
                <th class="text-center">Expéditions</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($pointsOfSale as $pointOfSale)
                <tr>
                  <td><strong>{{ $pointOfSale->name }}</strong></td>
                  <td><span class="badge bg-info text-dark">{{ $pointOfSale->getTypeLabel() }}</span></td>
                  <td>{{ $pointOfSale->address }}</td>
                  <td>{{ $pointOfSale->city }}</td>
                  <td class="text-center">{{ $pointOfSale->shipments_count }}</td>
                  <td class="text-end text-nowrap">
                    <a href="{{ route('pro.distributor.points-of-sale.show', $pointOfSale) }}" class="btn btn-sm btn-outline-secondary" title="Voir">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="{{ route('pro.distributor.points-of-sale.edit', $pointOfSale) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                      <i class="fa-solid fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('pro.distributor.points-of-sale.destroy', $pointOfSale) }}" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"
                              onclick="return confirm('Supprimer ce point de vente ?');">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        {{ $pointsOfSale->links('pagination::bootstrap-5') }}
      @else
        <div class="alert alert-info mb-0">
          <i class="fa-solid fa-circle-info"></i> Aucun point de vente pour le moment.
        </div>
      @endif
    </div>
  </div>
</div>
</div>
@endsection
