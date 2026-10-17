@extends('backend.layouts.app')

@section('title', 'Gestion des produits - Farm to Fork Admin')

@php
  use App\Models\Harvest;
  $query = request()->except(['page', 'status', 'view']);
  $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $viewUrl = fn ($v) => route('backend.products.index', array_merge(request()->except('page'), ['view' => $v]));
  $confirm = fn ($h) => "Supprimer le produit « " . addslashes($h->product_name) . " » (lot {$h->batch_code}) de " . addslashes($h->farm->user->company_name) . " ? Cette action est irréversible.";
@endphp

@section('extra-css')
  @include('backend.pages.partials.module-styles')
@endsection

@section('content')
<div class="m1-head">
  <div>
    <div class="m1-crumb"><a href="{{ route('backend.dashboard') }}">Back-office</a> / Produits & Catégories</div>
    <h3><i class="mdi mdi-basket"></i> Gestion des produits</h3>
    <p>Contrôle des produits récoltés par tous les producteurs : photo, informations, statut, suppression.</p>
  </div>
  <div class="m1-head__actions">
    <div class="m1-switch" role="group" aria-label="Affichage">
      <a href="{{ $viewUrl('table') }}" class="{{ $view === 'table' ? 'active' : '' }}" title="Vue tableau"><i class="mdi mdi-format-list-bulleted"></i> Tableau</a>
      <a href="{{ $viewUrl('grid') }}" class="{{ $view === 'grid' ? 'active' : '' }}" title="Vue grille"><i class="mdi mdi-view-grid"></i> Grille</a>
    </div>
    <a href="{{ route('backend.farms.index') }}" class="btn btn-outline-secondary"><i class="mdi mdi-barn"></i> Exploitations</a>
    <a href="{{ route('frontend.products') }}" target="_blank" class="btn btn-success"><i class="mdi mdi-open-in-new"></i> Catalogue public</a>
  </div>
</div>

@include('backend.pages.partials.flash')

<div class="m1-kpis">
  <div class="m1-kpi"><div class="m1-kpi__icon ic-green"><i class="mdi mdi-basket"></i></div><div><strong>{{ $stats['count'] }}</strong><span>Produits déclarés</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-blue"><i class="mdi mdi-account-group"></i></div><div><strong>{{ $stats['producers'] }}</strong><span>Producteurs actifs</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-teal"><i class="mdi mdi-weight-kilogram"></i></div><div><strong>{{ number_format($stats['kg'] / 1000, 1, ',', ' ') }} t</strong><span>Production totale</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-amber"><i class="mdi mdi-package-variant-closed"></i></div><div><strong>{{ $stats['in_stock'] }}</strong><span>En stock</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-purple"><i class="mdi mdi-star-circle"></i></div><div><strong>{{ $stats['grade_a'] }} %</strong><span>Qualité A</span></div></div>
  <a class="m1-kpi" href="{{ route('backend.products.index', ['photo' => 'without', 'view' => $view]) }}" title="Voir les produits sans photo">
    <div class="m1-kpi__icon ic-red"><i class="mdi mdi-image-off"></i></div><div><strong>{{ $stats['without_photo'] }}</strong><span>Sans photo</span></div>
  </a>
  <a class="m1-kpi" href="{{ route('backend.products.index', ['risk' => 'high', 'sort' => 'risk', 'view' => $view]) }}" title="Lots à contrôler en priorité">
    <div class="m1-kpi__icon ic-red"><i class="mdi mdi-shield-alert"></i></div><div><strong>{{ $stats['high_risk'] }}</strong><span>Risque élevé (IA)</span></div>
  </a>
</div>

<div class="m1-tabs">
  <a href="{{ route('backend.products.index', array_merge($query, ['view' => $view])) }}" class="m1-tab {{ empty($filters['status']) ? 'active' : '' }}">Tous <span class="count">{{ $stats['count'] }}</span></a>
  @foreach(Harvest::STATUSES as $k => $label)
    <a href="{{ route('backend.products.index', array_merge($query, ['status' => $k, 'view' => $view])) }}" class="m1-tab {{ ($filters['status'] ?? '') === $k ? 'active' : '' }}">{{ $label }} <span class="count">{{ $byStatus[$k] }}</span></a>
  @endforeach
</div>

<form method="GET" action="{{ route('backend.products.index') }}" class="m1-toolbar">
  <input type="hidden" name="view" value="{{ $view }}">
  @if(!empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
  <div class="m1-toolbar__grid">
    <div>
      <label for="q">Recherche</label>
      <div class="m1-search"><i class="mdi mdi-magnify"></i><input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Produit, variété, code lot…"></div>
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
      <label for="farm_id">Exploitation</label>
      <select id="farm_id" name="farm_id" class="form-control">
        <option value="">Toutes</option>
        @foreach($farms as $f)
          <option value="{{ $f->id }}" @selected((int) ($filters['farm_id'] ?? 0) === $f->id)>{{ $f->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label for="category">Catégorie</label>
      <select id="category" name="category" class="form-control">
        <option value="">Toutes</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}" @selected((int) ($filters['category'] ?? 0) === $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label for="quality">Qualité</label>
      <select id="quality" name="quality" class="form-control">
        <option value="">Toutes</option>
        @foreach(array_keys(Harvest::QUALITY_GRADES) as $g)
          <option value="{{ $g }}" @selected(($filters['quality'] ?? '') === $g)>{{ $g }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label for="photo">Photo</label>
      <select id="photo" name="photo" class="form-control">
        <option value="">Toutes</option>
        <option value="with" @selected(($filters['photo'] ?? '') === 'with')>Avec</option>
        <option value="without" @selected(($filters['photo'] ?? '') === 'without')>Sans</option>
      </select>
    </div>
    <div>
      <label for="risk">Conformité</label>
      <select id="risk" name="risk" class="form-control">
        <option value="">Toutes</option>
        <option value="high" @selected(($filters['risk'] ?? '') === 'high')>Risque élevé</option>
        <option value="medium" @selected(($filters['risk'] ?? '') === 'medium')>À surveiller</option>
        <option value="low" @selected(($filters['risk'] ?? '') === 'low')>Conforme</option>
      </select>
    </div>
    <div class="m1-toolbar__btns">
      <label class="d-flex align-items-center mb-0" style="gap:4px; text-transform:none; letter-spacing:0; font-size:.8rem; white-space:nowrap" title="Trier par score de risque">
        <input type="checkbox" name="sort" value="risk" @checked(request('sort') === 'risk')> Tri risque
      </label>
      <button type="submit" class="btn btn-success" title="Filtrer"><i class="mdi mdi-filter"></i></button>
      @if(array_filter(\Illuminate\Support\Arr::except($filters, 'status')))
        <a href="{{ route('backend.products.index', ['view' => $view]) }}" class="btn btn-light" title="Effacer les filtres"><i class="mdi mdi-close"></i></a>
      @endif
    </div>
  </div>
</form>

<div class="m1-panel">
  @if($harvests->isEmpty())
    <div class="m1-empty"><i class="mdi mdi-basket-off"></i>Aucun produit ne correspond à ces critères.</div>
  @elseif($view === 'grid')
    <div class="m1-grid">
      @foreach($harvests as $h)
        <div class="m1-card">
          <div class="m1-card__img">
            <img src="{{ $h->displayImageUrl() }}" alt="{{ $h->product_name }}" loading="lazy">
            @unless($h->hasPhoto())<span class="m1-chip m1-chip--photo" style="position:absolute;left:8px;bottom:8px"><i class="mdi mdi-image-off"></i> Sans photo</span>@endunless
            <span class="m1-grade m1-grade-{{ $h->quality_grade }}">{{ $h->quality_grade }}</span>
          </div>
          <div class="m1-card__body">
            <strong>{{ $h->product_name }}</strong>
            <span class="m1-sub">{{ $h->categoryLabel() }}{{ $h->variety ? ' · ' . $h->variety : '' }}</span>
            <div class="m1-producer mt-2">
              <span class="m1-avatar">{{ $initials($h->farm->user->company_name) }}</span>
              <div class="m1-owner">{{ $h->farm->user->company_name }}<small>{{ $h->farm->name }}</small></div>
            </div>
            <div class="mt-2 d-flex justify-content-between">
              <span>{{ number_format($h->quantity, 2, ',', ' ') }} {{ $h->unitShort() }}</span>
              <span class="text-muted">{{ $h->harvest_date->format('d/m/Y') }}</span>
            </div>
          </div>
          <div class="m1-card__foot">
            <span class="d-flex" style="gap:4px"><span class="m1-chip">{{ $h->statusLabel() }}</span><span class="m1-risk m1-risk--{{ $h->risk['level'] }}" title="Score de risque">{{ $h->risk['score'] }}</span></span>
            <span class="m1-actions">
              <a href="{{ route('traceability.show', $h->batch_code) }}" target="_blank" class="btn btn-sm btn-light" title="Page d'origine"><i class="mdi mdi-eye"></i></a>
              <a href="{{ route('backend.products.edit', $h) }}" class="btn btn-sm btn-info" title="Modifier"><i class="mdi mdi-pencil"></i></a>
              <form method="POST" action="{{ route('backend.products.destroy', $h) }}" class="d-inline" onsubmit="return confirm('{{ $confirm($h) }}');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="mdi mdi-delete"></i></button>
              </form>
            </span>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="table-responsive">
      <table class="table m1-table">
        <thead>
          <tr><th>Produit</th><th>Producteur</th><th>Récolte</th><th>Qualité</th><th title="Score de risque calculé par l'analyse de conformité">Conformité</th><th>Statut</th><th class="text-right">Actions</th></tr>
        </thead>
        <tbody>
          @foreach($harvests as $h)
            <tr>
              <td>
                <div class="m1-product">
                  <span style="position:relative; flex-shrink:0">
                    <img src="{{ $h->displayImageUrl() }}" alt="" class="m1-thumb" loading="lazy">
                    @unless($h->hasPhoto())<i class="mdi mdi-image-off" title="Illustration : sans photo" style="position:absolute;right:-4px;bottom:-4px;background:#fde8e8;color:#c53030;border-radius:50%;width:18px;height:18px;font-size:.7rem;display:flex;align-items:center;justify-content:center"></i>@endunless
                  </span>
                  <div>
                    <strong>{{ $h->product_name }}</strong>
                    <span class="m1-sub">{{ $h->categoryLabel() }}{{ $h->variety ? ' · ' . $h->variety : '' }}</span>
                    <code>{{ $h->batch_code }}</code>
                  </div>
                </div>
              </td>
              <td>
                <div class="m1-producer">
                  <span class="m1-avatar">{{ $initials($h->farm->user->company_name) }}</span>
                  <div class="m1-owner">{{ $h->farm->user->company_name }}<small>{{ $h->farm->name }} · {{ $h->farm->governorate }}</small></div>
                </div>
              </td>
              <td class="text-nowrap">
                <strong>{{ number_format($h->quantity, 2, ',', ' ') }} {{ $h->unitShort() }}</strong>
                <span class="m1-sub">{{ $h->harvest_date->format('d/m/Y') }}</span>
              </td>
              <td><span class="m1-grade m1-grade-{{ $h->quality_grade }}">{{ $h->quality_grade }}</span></td>
              <td>
                <span class="m1-risk m1-risk--{{ $h->risk['level'] }}" title="{{ collect($h->risk['reasons'])->pluck(1)->implode(' • ') ?: 'Aucune anomalie' }}">
                  <span class="m1-risk__bar"><span style="width: {{ $h->risk['score'] }}%"></span></span> {{ $h->risk['score'] }}
                </span>
              </td>
              <td>
                <form method="POST" action="{{ route('backend.products.status', $h) }}">
                  @csrf @method('PATCH')
                  <select name="status" class="form-control form-control-sm m1-status st-{{ $h->status }}" onchange="this.form.submit()" aria-label="Statut de {{ $h->product_name }}">
                    @foreach(Harvest::STATUSES as $k => $label)
                      <option value="{{ $k }}" @selected($h->status === $k)>{{ $label }}</option>
                    @endforeach
                  </select>
                </form>
              </td>
              <td class="text-right m1-actions">
                <a href="{{ route('traceability.show', $h->batch_code) }}" target="_blank" class="btn btn-sm btn-light" title="Page d'origine"><i class="mdi mdi-eye"></i></a>
                <a href="{{ route('backend.products.edit', $h) }}" class="btn btn-sm btn-info" title="Modifier"><i class="mdi mdi-pencil"></i></a>
                <form method="POST" action="{{ route('backend.products.destroy', $h) }}" class="d-inline" onsubmit="return confirm('{{ $confirm($h) }}');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="mdi mdi-delete"></i></button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  @if($harvests->isNotEmpty())
    <div class="m1-panel__foot">
      <span>{{ $harvests->firstItem() }}–{{ $harvests->lastItem() }} sur {{ $harvests->total() }} produit(s)</span>
      {{ $harvests->links('pagination::bootstrap-4') }}
    </div>
  @endif
</div>
@endsection
