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
    <!-- Search and Filter Section - IMPROVED DESIGN -->
    <div style="background: linear-gradient(135deg, #f8fafb 0%, #f0f5f2 100%); padding: 2rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid rgba(40, 167, 69, 0.1); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
      
      <!-- Section Title -->
      <div style="margin-bottom: 1.5rem;">
        <h4 style="margin: 0; color: #1e3a2b; font-weight: 700; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-filter" style="color: #28a745; font-size: 1.1rem;"></i>
          Filtrer et Rechercher
        </h4>
        <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.9rem;">Trouvez rapidement les lots dont vous avez besoin</p>
      </div>

      <form method="GET" action="{{ route('pro.processor.raw-materials.index') }}" id="filterForm" style="display: grid; gap: 1.25rem;">
        
        <!-- Search Row -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: end;">
          <div>
            <label for="search" style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e3a2b; font-size: 0.95rem;">
              <i class="fa-solid fa-magnifying-glass" style="color: #28a745; margin-right: 6px;"></i>Rechercher par mot-clé
            </label>
            <div style="position: relative;">
              <input 
                type="text" 
                id="search" 
                name="search" 
                placeholder="Produit, variété, producteur, farm..." 
                value="{{ $searchQuery }}"
                style="width: 100%; padding: 12px 16px 12px 40px; border: 2px solid #ddd; border-radius: 10px; font-size: 0.95rem; transition: all 0.3s; background: white;"
                onfocus="this.style.borderColor='#28a745'; this.style.boxShadow='0 0 0 3px rgba(40, 167, 69, 0.1)';"
                onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none';"
              >
              <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #999; pointer-events: none;"></i>
            </div>
          </div>

          <div>
            <label for="filter_status" style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e3a2b; font-size: 0.95rem;">
              <i class="fa-solid fa-list-check" style="color: #28a745; margin-right: 6px;"></i>Statut d'enregistrement
            </label>
            <select 
              id="filter_status" 
              name="filter_status" 
              style="width: 100%; padding: 12px 16px; border: 2px solid #ddd; border-radius: 10px; font-size: 0.95rem; background: white; transition: all 0.3s; cursor: pointer;"
              onchange="this.style.borderColor='#28a745';"
              onblur="this.style.borderColor='#ddd';"
            >
              <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>📦 Tous les lots</option>
              <option value="not_registered" {{ $filterStatus === 'not_registered' ? 'selected' : '' }}>✨ Pas encore enregistrés</option>
              <option value="registered" {{ $filterStatus === 'registered' ? 'selected' : '' }}>✅ Déjà enregistrés</option>
            </select>
          </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
          <button 
            type="submit" 
            style="background: linear-gradient(to right, #28a745, #1e7e34); color: white; border: none; padding: 12px 24px; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2);"
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(40, 167, 69, 0.3)';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(40, 167, 69, 0.2)';"
          >
            <i class="fa-solid fa-search"></i> Rechercher
          </button>
          
          @if($searchQuery || $filterStatus !== 'all')
            <a 
              href="{{ route('pro.processor.raw-materials.index') }}" 
              style="background: #f0f0f0; color: #333; border: 2px solid #ddd; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s;"
              onmouseover="this.style.background='#e8e8e8'; this.style.borderColor='#999';"
              onmouseout="this.style.background='#f0f0f0'; this.style.borderColor='#ddd';"
            >
              <i class="fa-solid fa-rotate-left"></i> Réinitialiser
            </a>
          @endif
        </div>
      </form>

      <!-- Active Filters Info -->
      @if($searchQuery || $filterStatus !== 'all')
        <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(40, 167, 69, 0.08); border-left: 4px solid #28a745; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
          <i class="fa-solid fa-circle-info" style="color: #28a745; font-size: 1.1rem;"></i>
          <div style="font-size: 0.9rem; color: #1e3a2b;">
            @if($searchQuery && $filterStatus === 'all')
              <strong>Recherche:</strong> "{{ $searchQuery }}"
            @elseif($filterStatus === 'registered')
              Affichage des lots <strong>déjà enregistrés</strong>
            @elseif($filterStatus === 'not_registered')
              Affichage des lots <strong>pas encore enregistrés</strong>
            @endif
            @if($searchQuery && $filterStatus !== 'all')
              <br><strong>Filtre:</strong> {{ $filterStatus === 'registered' ? 'déjà enregistrés' : 'pas encore enregistrés' }}
            @endif
          </div>
        </div>
      @endif
    </div>

    @if($availableHarvests->isEmpty())
      <div class="p-card empty-state">
        <i class="fa-solid fa-inbox d-block"></i>
        <p>
          @if($searchQuery || $filterStatus !== 'all')
            Aucun lot correspondant à vos critères
          @else
            Aucun lot disponible
          @endif
        </p>
        <small>
          @if($searchQuery || $filterStatus !== 'all')
            Essayez une autre recherche ou d'autres filtres
          @else
            Les producteurs n'ont pas encore créé de lots validés
          @endif
        </small>
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
      <div style="margin-top: 2.5rem;">
        <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1.5rem; background: #f8fafb; border-radius: 12px; border: 1px solid #e0e0e0;">
          <!-- Pagination Info -->
          <div style="font-size: 0.9rem; color: #666; display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-list"></i>
            <span><strong>{{ $availableHarvests->firstItem() ?? 1 }}</strong> - <strong>{{ $availableHarvests->lastItem() ?? $availableHarvests->count() }}</strong> de <strong>{{ $availableHarvests->total() }}</strong> lots</span>
          </div>

          <!-- Pagination Links -->
          <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center;">
            @if ($availableHarvests->onFirstPage())
              <span style="padding: 8px 12px; background: #e0e0e0; color: #999; border-radius: 6px; cursor: not-allowed; font-size: 0.9rem;">
                <i class="fa-solid fa-chevron-left"></i>
              </span>
            @else
              <a href="{{ $availableHarvests->previousPageUrl() }}" style="padding: 8px 12px; background: white; color: #28a745; border: 1px solid #28a745; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.background='#28a745'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#28a745';">
                <i class="fa-solid fa-chevron-left"></i> Précédent
              </a>
            @endif

            <!-- Page Numbers -->
            @foreach ($availableHarvests->getUrlRange(1, $availableHarvests->lastPage()) as $page => $url)
              @if ($page == $availableHarvests->currentPage())
                <span style="padding: 8px 12px; background: linear-gradient(to right, #28a745, #1e7e34); color: white; border-radius: 6px; font-weight: 600; min-width: 36px; text-align: center;">{{ $page }}</span>
              @else
                <a href="{{ $url }}" style="padding: 8px 12px; background: white; color: #28a745; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; transition: all 0.3s; min-width: 36px; text-align: center; display: inline-block;" onmouseover="this.style.background='#f0f5f2'; this.style.borderColor='#28a745';" onmouseout="this.style.background='white'; this.style.borderColor='#ddd';">{{ $page }}</a>
              @endif
            @endforeach

            @if ($availableHarvests->hasMorePages())
              <a href="{{ $availableHarvests->nextPageUrl() }}" style="padding: 8px 12px; background: white; color: #28a745; border: 1px solid #28a745; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.background='#28a745'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#28a745';">
                Suivant <i class="fa-solid fa-chevron-right"></i>
              </a>
            @else
              <span style="padding: 8px 12px; background: #e0e0e0; color: #999; border-radius: 6px; cursor: not-allowed; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 4px;">
                Suivant <i class="fa-solid fa-chevron-right"></i>
              </span>
            @endif
          </div>
        </div>
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
      
      <!-- Pagination for Receptions -->
      <div style="margin-top: 2.5rem;">
        <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1.5rem; background: #f8fafb; border-radius: 12px; border: 1px solid #e0e0e0;">
          <!-- Pagination Info -->
          <div style="font-size: 0.9rem; color: #666; display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-list"></i>
            <span><strong>{{ $receptions->firstItem() ?? 1 }}</strong> - <strong>{{ $receptions->lastItem() ?? $receptions->count() }}</strong> de <strong>{{ $receptions->total() }}</strong> réceptions</span>
          </div>

          <!-- Pagination Links -->
          <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center;">
            @if ($receptions->onFirstPage())
              <span style="padding: 8px 12px; background: #e0e0e0; color: #999; border-radius: 6px; cursor: not-allowed; font-size: 0.9rem;">
                <i class="fa-solid fa-chevron-left"></i>
              </span>
            @else
              <a href="{{ $receptions->previousPageUrl() }}" style="padding: 8px 12px; background: white; color: #28a745; border: 1px solid #28a745; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.background='#28a745'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#28a745';">
                <i class="fa-solid fa-chevron-left"></i> Précédent
              </a>
            @endif

            <!-- Page Numbers -->
            @foreach ($receptions->getUrlRange(1, $receptions->lastPage()) as $page => $url)
              @if ($page == $receptions->currentPage())
                <span style="padding: 8px 12px; background: linear-gradient(to right, #28a745, #1e7e34); color: white; border-radius: 6px; font-weight: 600; min-width: 36px; text-align: center;">{{ $page }}</span>
              @else
                <a href="{{ $url }}" style="padding: 8px 12px; background: white; color: #28a745; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; transition: all 0.3s; min-width: 36px; text-align: center; display: inline-block;" onmouseover="this.style.background='#f0f5f2'; this.style.borderColor='#28a745';" onmouseout="this.style.background='white'; this.style.borderColor='#ddd';">{{ $page }}</a>
              @endif
            @endforeach

            @if ($receptions->hasMorePages())
              <a href="{{ $receptions->nextPageUrl() }}" style="padding: 8px 12px; background: white; color: #28a745; border: 1px solid #28a745; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.background='#28a745'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#28a745';">
                Suivant <i class="fa-solid fa-chevron-right"></i>
              </a>
            @else
              <span style="padding: 8px 12px; background: #e0e0e0; color: #999; border-radius: 6px; cursor: not-allowed; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 4px;">
                Suivant <i class="fa-solid fa-chevron-right"></i>
              </span>
            @endif
          </div>
        </div>
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
