@extends('pro.layouts.producer')

@section('title', 'Mes récoltes | Farm to Fork')

@section('producer-content')
<div class="page-head">
  <div>
    <h1>Origine des récoltes</h1>
    <p>Chaque lot est rattaché à une exploitation et possède un QR code de traçabilité.</p>
  </div>
  <div class="page-head__actions">
    <a href="{{ route('pro.harvests.export', request()->query()) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-file-csv"></i> Exporter CSV</a>
    <a href="{{ route('pro.harvests.create') }}" class="btn btn-success"><i class="fa-solid fa-plus"></i> Nouvelle récolte</a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-boxes-stacked"></i></div><div><div class="stat__value">{{ $stats['count'] }}</div><div class="stat__label">Lots enregistrés</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ number_format($stats['kg'], 0, ',', ' ') }} kg</div><div class="stat__label">Production totale</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-warehouse"></i></div><div><div class="stat__value">{{ number_format($stats['in_stock_kg'], 0, ',', ' ') }} kg</div><div class="stat__label">En stock</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-award"></i></div><div><div class="stat__value">{{ $stats['grade_a'] }} %</div><div class="stat__label">Lots qualité A</div></div></div>
</div>

<form method="GET" action="{{ route('pro.harvests.index') }}" class="p-card mb-4">
  <div class="filters">
    <div>
      <label class="form-label" for="q">Recherche</label>
      <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Produit, variété, code lot…">
    </div>
    <div>
      <label class="form-label" for="f-farm">Exploitation</label>
      <select id="f-farm" name="farm_id" class="form-select">
        <option value="">Toutes</option>
        @foreach($farms as $f)
          <option value="{{ $f->id }}" @selected((int) ($filters['farm_id'] ?? 0) === $f->id)>{{ $f->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="form-label" for="f-cat">Catégorie</label>
      <select id="f-cat" name="category" class="form-select">
        <option value="">Toutes</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}" @selected((int) ($filters['category'] ?? 0) === $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="form-label" for="f-from">Du</label>
      <input type="date" id="f-from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
    </div>
    <div>
      <label class="form-label" for="f-to">Au</label>
      <input type="date" id="f-to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-success" type="submit"><i class="fa-solid fa-filter"></i> Filtrer</button>
      @if(array_filter($filters))
        <a href="{{ route('pro.harvests.index') }}" class="btn btn-light" title="Réinitialiser"><i class="fa-solid fa-rotate-left"></i></a>
      @endif
    </div>
  </div>
</form>

<div class="p-card">
  @if($harvests->isEmpty())
    <div class="empty-state">
      <i class="fa-solid fa-basket-shopping d-block"></i>
      @if(array_filter($filters))
        <p>Aucune récolte ne correspond à vos critères.</p>
        <a href="{{ route('pro.harvests.index') }}" class="btn btn-outline-success">Réinitialiser les filtres</a>
      @else
        <p>Aucune récolte déclarée pour le moment.</p>
        <a href="{{ route('pro.harvests.create') }}" class="btn btn-success"><i class="fa-solid fa-plus"></i> Déclarer ma première récolte</a>
      @endif
    </div>
  @else
    <div class="table-responsive">
      <table class="p-table">
        <thead>
          <tr><th>Code lot</th><th>Produit</th><th>Exploitation</th><th>Récolte</th><th>Quantité</th><th>Rendement</th><th>Qualité</th><th>Statut</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          @foreach($harvests as $h)
            <tr>
              <td><code>{{ $h->batch_code }}</code></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="{{ $h->displayImageUrl() }}" alt="" class="thumb" loading="lazy" @unless($h->hasPhoto()) title="Illustration — ajoutez une photo" style="opacity:.85" @endunless>
                  <div><strong>{{ $h->product_name }}</strong><br><small class="text-muted">{{ $h->categoryLabel() }}{{ $h->variety ? ' · ' . $h->variety : '' }}</small></div>
                </div>
              </td>
              <td><a href="{{ route('pro.farms.show', $h->farm) }}">{{ $h->farm->name }}</a>@if($h->plot_name)<br><small class="text-muted">{{ $h->plot_name }}</small>@endif</td>
              <td>{{ $h->harvest_date->format('d/m/Y') }}</td>
              <td class="text-nowrap">{{ number_format($h->quantity, 2, ',', ' ') }} {{ $h->unitShort() }}</td>
              <td class="text-nowrap">{{ $h->yieldPerHectare() !== null ? number_format($h->yieldPerHectare(), 0, ',', ' ') . ' kg/ha' : '—' }}</td>
              <td><span class="grade grade--{{ $h->quality_grade }}">{{ $h->quality_grade }}</span></td>
              <td><span class="tag tag--{{ $h->status }}">{{ $h->statusLabel() }}</span></td>
              <td class="text-end text-nowrap">
                <a href="{{ route('pro.harvests.show', $h) }}" class="btn btn-sm btn-light" title="Détail & QR code"><i class="fa-solid fa-qrcode"></i></a>
                <a href="{{ route('pro.harvests.edit', $h) }}" class="btn btn-sm btn-light" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                <form method="POST" action="{{ route('pro.harvests.destroy', $h) }}" class="d-inline"
                      data-confirm="Supprimer le lot {{ $h->batch_code }} ({{ $h->product_name }}) ? Son QR code ne fonctionnera plus.">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="mt-3 d-flex justify-content-center">{{ $harvests->links('pagination::bootstrap-5') }}</div>
  @endif
</div>
@endsection
