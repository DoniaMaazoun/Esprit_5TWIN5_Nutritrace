@extends('backend.layouts.app')

@section('title', 'Catégories - Farm to Fork Admin')

@section('extra-css')
  @include('backend.pages.categories.styles')
@endsection

@section('content')
<div class="m1-head">
  <div>
    <div class="m1-crumb"><a href="{{ route('backend.dashboard') }}">Back-office</a> / Produits & Catégories</div>
    <h3><i class="mdi mdi-tag-multiple"></i> Catégories de produits</h3>
    <p>Les producteurs classent chaque produit dans une catégorie active. Une catégorie utilisée ne peut pas être supprimée.</p>
  </div>
  <div class="m1-head__actions">
    <a href="{{ route('backend.products.index') }}" class="btn btn-outline-secondary"><i class="mdi mdi-basket"></i> Produits</a>
    <a href="{{ route('frontend.products') }}" target="_blank" class="btn btn-success"><i class="mdi mdi-open-in-new"></i> Catalogue public</a>
  </div>
</div>

@include('backend.pages.partials.flash')

<div class="m1-kpis">
  <div class="m1-kpi"><div class="m1-kpi__icon ic-green"><i class="mdi mdi-tag-multiple"></i></div><div><strong>{{ $stats['total'] }}</strong><span>Catégories</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-blue"><i class="mdi mdi-check-circle"></i></div><div><strong>{{ $stats['active'] }}</strong><span>Actives</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-amber"><i class="mdi mdi-tag-outline"></i></div><div><strong>{{ $stats['unused'] }}</strong><span>Sans produit</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-purple"><i class="mdi mdi-basket"></i></div><div><strong>{{ $stats['products'] }}</strong><span>Produits classés</span></div></div>
</div>

<div class="cat-layout">
  <div>
    <form method="GET" action="{{ route('backend.categories.index') }}" class="m1-toolbar">
      <div class="d-flex flex-wrap align-items-end" style="gap:10px">
        <div style="flex:1; min-width:220px">
          <label for="q">Recherche</label>
          <div class="m1-search"><i class="mdi mdi-magnify"></i><input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Nom ou description…"></div>
        </div>
        <div>
          <label for="status">Statut</label>
          <select id="status" name="status" class="form-control">
            <option value="">Toutes</option>
            <option value="active" @selected($filters['status'] === 'active')>Actives</option>
            <option value="inactive" @selected($filters['status'] === 'inactive')>Désactivées</option>
          </select>
        </div>
        <button type="submit" class="btn btn-success"><i class="mdi mdi-filter"></i> Filtrer</button>
        @if($filters['q'] || $filters['status'])
          <a href="{{ route('backend.categories.index') }}" class="btn btn-light"><i class="mdi mdi-close"></i></a>
        @endif
      </div>
    </form>

    <div class="m1-panel">
      @if($categories->isEmpty())
        <div class="m1-empty"><i class="mdi mdi-tag-off"></i>Aucune catégorie trouvée.</div>
      @else
        <div class="table-responsive">
          <table class="table m1-table">
            <thead><tr><th>Catégorie</th><th>Produits</th><th>Production</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
              @foreach($categories as $c)
                @php($kg = $kgByCategory[$c->id] ?? 0)
                <tr class="{{ $c->is_active ? '' : 'cat-off' }}">
                  <td>
                    <div class="cat-name">
                      <span class="cat-badge" style="background: {{ $c->color }}"><i class="fa-solid {{ $c->icon }}"></i></span>
                      <div><strong>{{ $c->name }}</strong><span class="m1-sub">{{ \Illuminate\Support\Str::limit($c->description, 60) ?: '—' }}</span></div>
                    </div>
                  </td>
                  <td>
                    @if($c->harvests_count)
                      <a href="{{ route('backend.products.index', ['category' => $c->id]) }}"><strong>{{ $c->harvests_count }}</strong> produit(s)</a>
                    @else
                      <span class="text-muted">0</span>
                    @endif
                  </td>
                  <td style="min-width:150px">
                    <div class="cat-bar"><span style="width: {{ round($kg / $maxKg * 100) }}%; background: {{ $c->color }}"></span></div>
                    <span class="m1-sub">{{ number_format($kg / 1000, 1, ',', ' ') }} t</span>
                  </td>
                  <td>
                    <form method="POST" action="{{ route('backend.categories.toggle', $c) }}">
                      @csrf @method('PATCH')
                      <button type="submit" class="m1-chip border-0" style="cursor:pointer; {{ $c->is_active ? 'background:#e6f6ec;color:#22663a' : '' }}" title="Cliquer pour {{ $c->is_active ? 'désactiver' : 'activer' }}">
                        <i class="mdi mdi-{{ $c->is_active ? 'check-circle' : 'pause-circle' }}"></i> {{ $c->is_active ? 'Active' : 'Désactivée' }}
                      </button>
                    </form>
                  </td>
                  <td class="text-right m1-actions">
                    <a href="{{ route('backend.categories.edit', $c) }}" class="btn btn-sm btn-info" title="Modifier"><i class="mdi mdi-pencil"></i></a>
                    <form method="POST" action="{{ route('backend.categories.destroy', $c) }}" class="d-inline"
                          onsubmit="return confirm('Supprimer la catégorie « {{ addslashes($c->name) }} » ?');">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger" title="{{ $c->harvests_count ? 'Utilisée par des produits : suppression impossible' : 'Supprimer' }}" @disabled($c->harvests_count)><i class="mdi mdi-delete"></i></button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  <aside class="cat-side">
    <h5><i class="mdi mdi-plus-circle text-success"></i> Nouvelle catégorie</h5>
    @include('backend.pages.categories._form', ['action' => route('backend.categories.store')])
  </aside>
</div>
@endsection

@section('extra-js')
  <script>window.AGRI = { csrf: @json(csrf_token()) };</script>
  <script src="{{ asset('frontend/js/producer.js') }}"></script>
  @stack('category-scripts')
@endsection
