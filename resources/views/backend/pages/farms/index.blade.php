@extends('backend.layouts.app')

@section('title', 'Exploitations - Farm to Fork Admin')

@php
  use App\Models\Farm;
  $query = request()->except(['page', 'status']);
  $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $typeColors = ['organic' => 'background:#d8f3dc;color:#1b4332', 'conventional' => '', 'reasoned' => 'background:#e0f2fe;color:#075985',
                 'permaculture' => 'background:#fef3c7;color:#92400e', 'agroforestry' => 'background:#ede9fe;color:#5b21b6'];
@endphp

@section('extra-css')
  @include('backend.pages.partials.module-styles')
  <style>
    .m1-status.st-active { background: #e6f6ec; color: #22663a; border-color: #bfe3cb; }
    .m1-status.st-fallow { background: #fff5dc; color: #8a5a00; border-color: #f2dda2; }
    .m1-status.st-inactive { background: #fdecec; color: #8f2424; border-color: #f5c6c6; }
    .farm-icon { width: 44px; height: 44px; border-radius: 10px; background: var(--m1-mint); color: var(--m1-green); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
  </style>
@endsection

@section('content')
<div class="m1-head">
  <div>
    <div class="m1-crumb"><a href="{{ route('backend.dashboard') }}">Back-office</a> / Fermes</div>
    <h3><i class="mdi mdi-barn"></i> Exploitations des producteurs</h3>
    <p>Contrôle de toutes les exploitations : statut, informations, suppression.</p>
  </div>
  <div class="m1-head__actions">
    <a href="{{ route('backend.products.index') }}" class="btn btn-outline-secondary"><i class="mdi mdi-basket"></i> Produits</a>
    <a href="{{ route('frontend.products') }}" target="_blank" class="btn btn-success"><i class="mdi mdi-open-in-new"></i> Catalogue public</a>
  </div>
</div>

@include('backend.pages.partials.flash')

<div class="m1-kpis">
  <div class="m1-kpi"><div class="m1-kpi__icon ic-green"><i class="mdi mdi-barn"></i></div><div><strong>{{ $stats['farms'] }}</strong><span>Exploitations</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-blue"><i class="mdi mdi-account-group"></i></div><div><strong>{{ $stats['producers'] }}</strong><span>Producteurs</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-teal"><i class="mdi mdi-texture"></i></div><div><strong>{{ number_format($stats['area'], 1, ',', ' ') }} ha</strong><span>Surface totale</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-green"><i class="mdi mdi-sprout"></i></div><div><strong>{{ $stats['organic'] }}</strong><span>En agriculture bio</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-purple"><i class="mdi mdi-map-marker"></i></div><div><strong>{{ $stats['located'] }}</strong><span>Géolocalisées</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-amber"><i class="mdi mdi-pause-circle"></i></div><div><strong>{{ $stats['inactive'] }}</strong><span>En jachère / inactives</span></div></div>
</div>

<div class="m1-tabs">
  <a href="{{ route('backend.farms.index', $query) }}" class="m1-tab {{ empty($filters['status']) ? 'active' : '' }}">Toutes <span class="count">{{ $stats['farms'] }}</span></a>
  @foreach(Farm::STATUSES as $k => $label)
    <a href="{{ route('backend.farms.index', array_merge($query, ['status' => $k])) }}" class="m1-tab {{ ($filters['status'] ?? '') === $k ? 'active' : '' }}">{{ $label }} <span class="count">{{ $byStatus[$k] }}</span></a>
  @endforeach
</div>

<form method="GET" action="{{ route('backend.farms.index') }}" class="m1-toolbar">
  @if(!empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
  <div class="m1-toolbar__grid" style="grid-template-columns: 2fr 1.4fr 1.2fr 1.2fr auto">
    <div>
      <label for="q">Recherche</label>
      <div class="m1-search"><i class="mdi mdi-magnify"></i><input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Nom, ville, culture…"></div>
    </div>
    <div>
      <label for="user_id">Producteur</label>
      <select id="user_id" name="user_id" class="form-control">
        <option value="">Tous</option>
        @foreach($producers as $p)
          <option value="{{ $p->id }}" @selected((int) ($filters['user_id'] ?? 0) === $p->id)>{{ $p->company_name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label for="governorate">Gouvernorat</label>
      <select id="governorate" name="governorate" class="form-control">
        <option value="">Tous</option>
        @foreach(Farm::GOVERNORATES as $g)
          <option value="{{ $g }}" @selected(($filters['governorate'] ?? '') === $g)>{{ $g }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label for="farming_type">Agriculture</label>
      <select id="farming_type" name="farming_type" class="form-control">
        <option value="">Toutes</option>
        @foreach(Farm::FARMING_TYPES as $k => $label)
          <option value="{{ $k }}" @selected(($filters['farming_type'] ?? '') === $k)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="m1-toolbar__btns">
      <button type="submit" class="btn btn-success" title="Filtrer"><i class="mdi mdi-filter"></i></button>
      @if(array_filter(\Illuminate\Support\Arr::except($filters, 'status')))
        <a href="{{ route('backend.farms.index') }}" class="btn btn-light" title="Effacer"><i class="mdi mdi-close"></i></a>
      @endif
    </div>
  </div>
</form>

<div class="m1-panel">
  @if($farms->isEmpty())
    <div class="m1-empty"><i class="mdi mdi-barn"></i>Aucune exploitation trouvée.</div>
  @else
    <div class="table-responsive">
      <table class="table m1-table">
        <thead><tr><th>Exploitation</th><th>Producteur</th><th>Surface</th><th>Agriculture</th><th>Produits</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          @foreach($farms as $farm)
            <tr>
              <td>
                <div class="m1-product">
                  <span class="farm-icon"><i class="mdi mdi-barn"></i></span>
                  <div>
                    <strong>{{ $farm->name }}</strong>
                    <span class="m1-sub"><i class="mdi mdi-map-marker"></i> {{ $farm->city }}, {{ $farm->governorate }}</span>
                    @if($farm->certification)<span class="m1-sub text-success"><i class="mdi mdi-certificate"></i> {{ $farm->certification }}</span>@endif
                  </div>
                </div>
              </td>
              <td>
                <div class="m1-producer">
                  <span class="m1-avatar">{{ $initials($farm->user->company_name ?? $farm->user->name) }}</span>
                  <div class="m1-owner">{{ $farm->user->company_name }}<small>{{ $farm->user->email }}</small></div>
                </div>
              </td>
              <td class="text-nowrap"><strong>{{ number_format($farm->total_area, 2, ',', ' ') }} ha</strong><span class="m1-sub">{{ $farm->irrigationTypeLabel() }}</span></td>
              <td><span class="m1-chip" style="{{ $typeColors[$farm->farming_type] ?? '' }}">{{ $farm->farmingTypeLabel() }}</span></td>
              <td><a href="{{ route('backend.products.index', ['farm_id' => $farm->id]) }}" class="m1-chip">{{ $farm->harvests_count }} <i class="mdi mdi-arrow-right"></i></a></td>
              <td>
                <form method="POST" action="{{ route('backend.farms.status', $farm) }}">
                  @csrf @method('PATCH')
                  <select name="status" class="form-control form-control-sm m1-status st-{{ $farm->status }}" onchange="this.form.submit()" aria-label="Statut de {{ $farm->name }}">
                    @foreach(Farm::STATUSES as $k => $label)
                      <option value="{{ $k }}" @selected($farm->status === $k)>{{ $label }}</option>
                    @endforeach
                  </select>
                </form>
              </td>
              <td class="text-right m1-actions">
                <a href="{{ route('backend.farms.edit', $farm) }}" class="btn btn-sm btn-info" title="Modifier"><i class="mdi mdi-pencil"></i></a>
                <form method="POST" action="{{ route('backend.farms.destroy', $farm) }}" class="d-inline"
                      onsubmit="return confirm('Supprimer l\'exploitation « {{ addslashes($farm->name) }} » de {{ addslashes($farm->user->company_name) }}{{ $farm->harvests_count ? ' et ses ' . $farm->harvests_count . ' produit(s)' : '' }} ?');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="mdi mdi-delete"></i></button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="m1-panel__foot">
      <span>{{ $farms->firstItem() }}–{{ $farms->lastItem() }} sur {{ $farms->total() }} exploitation(s)</span>
      {{ $farms->links('pagination::bootstrap-4') }}
    </div>
  @endif
</div>
@endsection
