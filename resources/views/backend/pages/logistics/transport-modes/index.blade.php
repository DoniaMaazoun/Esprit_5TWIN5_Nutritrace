@extends('backend.layouts.app')

@section('title', 'Modes de transport - Farm to Fork Admin')

@section('content')
<div class="row">
  <div class="col-md-12">
    <h2 class="page-title mb-4"><i class="mdi mdi-truck"></i> Modes de transport & facteurs d'émission</h2>
  </div>
</div>

@include('backend.pages.logistics.partials.alerts')

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <p class="text-muted mb-0">Facteurs en kg CO2e par tonne.km. CO2 = distance × poids (t) × facteur.</p>
          <a href="{{ route('backend.logistics.transport-modes.create') }}" class="btn btn-success btn-sm">
            <i class="mdi mdi-plus"></i> Nouveau mode
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Code</th>
                <th class="text-right">Facteur (kg CO2e/t.km)</th>
                <th>Source</th>
                <th>Statut</th>
                <th class="text-center">Étapes</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($transportModes as $transportMode)
                <tr>
                  <td><strong>{{ $transportMode->name }}</strong></td>
                  <td><code>{{ $transportMode->code }}</code></td>
                  <td class="text-right">{{ number_format($transportMode->emission_factor, 5, ',', ' ') }}</td>
                  <td>{{ $transportMode->source ?? '—' }}</td>
                  <td>
                    @if($transportMode->is_active)
                      <span class="badge badge-success">Actif</span>
                    @else
                      <span class="badge badge-secondary">Inactif</span>
                    @endif
                  </td>
                  <td class="text-center">{{ $transportMode->legs_count }}</td>
                  <td class="text-right text-nowrap">
                    <a href="{{ route('backend.logistics.transport-modes.edit', $transportMode) }}" class="btn btn-sm btn-primary">
                      <i class="mdi mdi-pencil"></i> Modifier
                    </a>
                    <form method="POST" action="{{ route('backend.logistics.transport-modes.destroy', $transportMode) }}" style="display: inline;">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce mode de transport ?');">
                        <i class="mdi mdi-delete"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted">Aucun mode de transport. Lancez le seeder TransportModeSeeder.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        {{ $transportModes->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>
</div>
@endsection
