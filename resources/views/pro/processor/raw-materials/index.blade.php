@extends('pro.layouts.processor')

@section('title', 'Réception des Matières Brutes - Transformateur | Farm to Fork')

@section('processor-content')

<style>
  .tab-buttons { display: flex; gap: 0; margin-bottom: 2rem; border-bottom: 2px solid var(--gray-light); }
  .tab-button { padding: 1rem 1.5rem; cursor: pointer; border: none; background: none; font-weight: 600; color: var(--gray); border-bottom: 3px solid transparent; transition: all .3s; }
  .tab-button.active { color: var(--processor-primary); border-bottom-color: var(--processor-primary); }
  .tab-pane { display: none; }
  .tab-pane.active { display: block; }
  .harvest-card { background: #fff; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm); border-left: 4px solid var(--processor-primary); display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; transition: all .3s; }
  .harvest-card:hover { box-shadow: var(--shadow-md); }
  .harvest-card.registered { opacity: .7; border-left-color: #ccc; }
  .harvest-info h3 { font-size: 1.1rem; font-weight: 700; color: var(--dark); margin: 0 0 0.75rem 0; }
  .harvest-meta { display: flex; gap: 1rem; font-size: .85rem; color: var(--gray); flex-wrap: wrap; }
  .harvest-meta span { display: flex; align-items: center; gap: 6px; }
  .harvest-meta i { color: var(--processor-primary); width: 14px; text-align: center; }
  .btn-receive { background: var(--processor-primary); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; gap: 6px; align-items: center; transition: all .3s; white-space: nowrap; }
  .btn-receive:hover { background: var(--processor-primary-dark); }
  .btn-disabled { background: #ccc; cursor: not-allowed; opacity: .6; }
  .badge-registered { background: #f0f9f4; color: #1B4332; font-size: .75rem; padding: 4px 8px; border-radius: 4px; }
  
  @media (max-width: 768px) { .harvest-card { flex-direction: column; align-items: flex-start; } .harvest-meta { width: 100%; } }
</style>

<section class="catalog-hero">
  <div class="container">
    <h1><i class="fa-solid fa-inbox"></i> Réception des Matières Brutes</h1>
    <p>Enregistrez et gérez la réception des lots du producteur à votre usine de transformation</p>
  </div>
</section>

<div class="container catalog-body">
  <div class="stat-grid">
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="stat__value">{{ $stats['total_pending'] }}</div><div class="stat__label">En attente</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-check-circle"></i></div><div><div class="stat__value">{{ $stats['total_received'] }}</div><div class="stat__label">Reçues</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-times-circle"></i></div><div><div class="stat__value">{{ $stats['total_rejected'] }}</div><div class="stat__label">Rejetées</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ number_format($stats['total_kg_received'], 0) }}</div><div class="stat__label">kg reçus</div></div></div>
  </div>

  <!-- Tabs -->
  <div class="tab-buttons">
    <button class="tab-button active" onclick="switchTab('available')">
      <i class="fa-solid fa-plus-circle"></i> Lots Disponibles
    </button>
    <button class="tab-button" onclick="switchTab('receptions')">
      <i class="fa-solid fa-history"></i> Historique des Réceptions
    </button>
  </div>

  <!-- TAB 1: Available Harvests -->
  <div id="available" class="tab-pane active">
    @if($availableHarvests->isEmpty())
      <div class="p-card empty-state">
        <i class="fa-solid fa-inbox d-block"></i>
        <p>Aucun lot disponible</p>
        <small>Les producteurs n'ont pas encore créé de lots validés</small>
      </div>
    @else
      <div style="margin-bottom: 2rem;">
        @foreach($availableHarvests as $harvest)
          @php
            $isRegistered = in_array($harvest->id, $registeredHarvestIds);
          @endphp
          <div class="harvest-card {{ $isRegistered ? 'registered' : '' }}">
            <div class="harvest-info" style="flex: 1;">
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <h3 style="margin: 0;">{{ $harvest->product_name }}</h3>
                @if($isRegistered)
                  <span class="badge-registered"><i class="fa-solid fa-check"></i> Enregistré</span>
                @endif
              </div>
              <div class="harvest-meta">
                <span><i class="fa-solid fa-tractor"></i> {{ $harvest->farm->name }}</span>
                <span><i class="fa-solid fa-user"></i> {{ $harvest->farm->user->company_name }}</span>
                <span><i class="fa-solid fa-calendar-check"></i> {{ $harvest->harvest_date?->format('d/m/Y') ?? 'N/A' }}</span>
                <span><i class="fa-solid fa-weight-hanging"></i> {{ $harvest->quantityInKg() }} kg</span>
                <span><i class="fa-solid fa-tag"></i> <span class="tag" style="background: var(--processor-primary); color: #fff; display: inline-block; font-size: .8rem;">{{ $harvest->category->name ?? 'N/A' }}</span></span>
              </div>
            </div>
            @if(!$isRegistered)
              <a href="{{ route('pro.processor.raw-materials.create', $harvest) }}" class="btn-receive">
                <i class="fa-solid fa-plus"></i> Enregistrer
              </a>
            @else
              <button disabled class="btn-receive btn-disabled" title="Ce lot a déjà été enregistré">
                <i class="fa-solid fa-check"></i> Enregistré
              </button>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    @if(!$availableHarvests->isEmpty())
      <div class="mt-4 d-flex justify-content-center">
        {{ $availableHarvests->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  <!-- TAB 2: Reception History -->
  <div id="receptions" class="tab-pane">
    @if($receptions->isEmpty())
      <div class="p-card empty-state">
        <i class="fa-solid fa-history d-block"></i>
        <p>Aucune réception enregistrée</p>
        <small>Commencez par enregistrer une réception de lot</small>
      </div>
    @else
      <div class="p-card">
        <div class="table-responsive">
          <table class="p-table">
            <thead>
              <tr>
                <th>Produit</th>
                <th>Producteur</th>
                <th>Quantité Reçue</th>
                <th>Date Réception</th>
                <th>Qualité</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($receptions as $reception)
                <tr>
                  <td>
                    <strong>{{ $reception->harvest->product_name }}</strong><br>
                    <small class="text-muted">{{ $reception->harvest->category->name ?? 'N/A' }}</small>
                  </td>
                  <td>{{ $reception->harvest->farm->user->company_name }}</td>
                  <td class="text-nowrap">{{ number_format($reception->quantity_received, 2) }} {{ $reception->unit }}</td>
                  <td class="text-nowrap">{{ $reception->reception_date->format('d/m/Y H:i') }}</td>
                  <td>
                    <span class="tag" style="background: {{ $reception->quality_status === 'good' ? '#d4edda' : ($reception->quality_status === 'acceptable' ? '#fff3cd' : '#f8d7da') }}; color: {{ $reception->quality_status === 'good' ? '#155724' : ($reception->quality_status === 'acceptable' ? '#856404' : '#721c24') }}; display: inline-block;">
                      {{ $reception->getQualityLabel() }}
                    </span>
                  </td>
                  <td>
                    <span class="tag tag--{{ $reception->status }}">
                      {{ $reception->getStatusLabel() }}
                    </span>
                  </td>
                  <td class="text-end text-nowrap">
                    <a href="{{ route('pro.processor.raw-materials.show', $reception) }}" class="btn btn-sm btn-light" title="Voir"><i class="fa-solid fa-eye"></i></a>
                    @if($reception->isPending())
                      <a href="{{ route('pro.processor.raw-materials.edit', $reception) }}" class="btn btn-sm btn-light" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="mt-4 d-flex justify-content-center">
        {{ $receptions->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>
</div>

<script>
  function switchTab(tabName) {
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.closest('.tab-button').classList.add('active');
  }
</script>

@endsection
