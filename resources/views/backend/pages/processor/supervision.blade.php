@extends('backend.layouts.app')

@section('title', 'Supervision Transformateurs - Farm to Fork Admin')

@php
  $tabs = [
      'receptions' => ['📦 Matières Reçues', 'inbox', $stats['total_receptions'], '#28a745'],
      'recipes' => ['🧪 Recettes Registrées', 'flask', $stats['total_recipes'], '#0066cc'],
      'production' => ['🏭 Production & QR Codes', 'factory', $stats['total_production_lots'], '#ff6b35'],
  ];
@endphp

@section('extra-css')
  <style>
    /* Page Header */
    .proc-head {
      margin-bottom: 2rem;
    }
    
    .proc-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: #1e3a2b;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .proc-subtitle {
      color: #666;
      font-size: 0.95rem;
      margin-bottom: 1.5rem;
    }
    
    /* KPI Grid */
    .proc-kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }
    
    .proc-kpi-card {
      background: white;
      border-radius: 10px;
      padding: 1.25rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      border-left: 4px solid #28a745;
      transition: all 0.3s;
    }
    
    .proc-kpi-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
      transform: translateY(-2px);
    }
    
    .proc-kpi-label {
      font-size: 0.8rem;
      color: #999;
      text-transform: uppercase;
      font-weight: 600;
      margin-bottom: 4px;
    }
    
    .proc-kpi-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: #1e3a2b;
    }
    
    /* Tabs */
    .proc-tabs {
      display: flex;
      gap: 1rem;
      border-bottom: 2px solid #e0e0e0;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }
    
    .proc-tab {
      padding: 1rem 1.5rem;
      cursor: pointer;
      border: none;
      background: none;
      font-weight: 600;
      color: #999;
      transition: all 0.3s;
      border-bottom: 3px solid transparent;
      position: relative;
      bottom: -2px;
    }
    
    .proc-tab:hover {
      color: #28a745;
    }
    
    .proc-tab.active {
      color: #28a745;
      border-bottom-color: #28a745;
    }
    
    /* Tab Pane */
    .proc-tab-pane {
      display: none;
    }
    
    .proc-tab-pane.active {
      display: block;
      animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    /* Table Styles */
    .proc-table-container {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    
    .proc-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .proc-table thead {
      background: #f8fafb;
      border-bottom: 2px solid #e0e0e0;
    }
    
    .proc-table th {
      padding: 1rem 1.25rem;
      text-align: left;
      font-size: 0.85rem;
      font-weight: 700;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .proc-table tbody tr {
      border-bottom: 1px solid #e8e8e8;
      transition: all 0.2s;
    }
    
    .proc-table tbody tr:hover {
      background: #f8fafb;
    }
    
    .proc-table td {
      padding: 1rem 1.25rem;
      color: #333;
      font-size: 0.95rem;
    }
    
    .proc-table td strong {
      color: #1e3a2b;
      font-weight: 600;
    }
    
    /* Status Badge */
    .proc-badge {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      text-align: center;
    }
    
    .proc-badge-received {
      background: #d4edda;
      color: #155724;
    }
    
    .proc-badge-pending {
      background: #fff3cd;
      color: #856404;
    }
    
    .proc-badge-rejected {
      background: #f8d7da;
      color: #721c24;
    }
    
    .proc-badge-active {
      background: #d1ecf1;
      color: #0c5460;
    }
    
    .proc-badge-inactive {
      background: #f0f0f0;
      color: #666;
    }
    
    .proc-badge-high-eco {
      background: #d4edda;
      color: #155724;
    }
    
    .proc-badge-medium-eco {
      background: #fff3cd;
      color: #856404;
    }
    
    .proc-badge-low-eco {
      background: #f8d7da;
      color: #721c24;
    }
    
    /* Company Name */
    .proc-company {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .proc-company-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #28a745, #1e7e34);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.9rem;
    }
    
    .proc-company-info h4 {
      margin: 0;
      color: #1e3a2b;
      font-weight: 600;
      font-size: 0.95rem;
    }
    
    .proc-company-info p {
      margin: 2px 0 0 0;
      color: #999;
      font-size: 0.8rem;
    }
    
    /* QR Code Link */
    .proc-qr-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 0.4rem 0.8rem;
      background: #f0f5f2;
      color: #28a745;
      text-decoration: none;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .proc-qr-link:hover {
      background: #28a745;
      color: white;
    }
    
    /* Empty State */
    .proc-empty-state {
      text-align: center;
      padding: 3rem 2rem;
      color: #999;
    }
    
    .proc-empty-state i {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    /* Pagination */
    .proc-pagination {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 1.5rem;
      flex-wrap: wrap;
    }
    
    .proc-pagination a,
    .proc-pagination span {
      padding: 0.5rem 0.75rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 0.85rem;
      text-decoration: none;
      color: #333;
      transition: all 0.3s;
    }
    
    .proc-pagination a:hover {
      background: #28a745;
      color: white;
      border-color: #28a745;
    }
    
    .proc-pagination span.active {
      background: #28a745;
      color: white;
      border-color: #28a745;
    }
    
    .proc-pagination span.disabled {
      color: #ccc;
      cursor: not-allowed;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .proc-tabs {
        flex-direction: column;
        border-bottom: none;
      }
      
      .proc-tab {
        border-bottom: none;
        border-left: 3px solid transparent;
        padding: 0.75rem 1rem;
        bottom: auto;
      }
      
      .proc-tab.active {
        border-left-color: #28a745;
        border-bottom: none;
      }
      
      .proc-table {
        font-size: 0.85rem;
      }
      
      .proc-table th,
      .proc-table td {
        padding: 0.75rem;
      }
    }
  </style>
@endsection

@section('content')
  <!-- Header -->
  <div class="proc-head">
    <h1 class="proc-title">
      <i class="fa-solid fa-industry"></i> Supervision Transformateurs
    </h1>
    <p class="proc-subtitle">
      Vue globale de toutes les opérations : réceptions de matières premières, recettes créées et lots de production.
    </p>
  </div>

  <!-- KPI Cards -->
  <div class="proc-kpi-grid">
    <!-- Receptions Stats -->
    <div class="proc-kpi-card">
      <div class="proc-kpi-label">📦 Réceptions Totales</div>
      <div class="proc-kpi-value">{{ $stats['total_receptions'] }}</div>
      <div style="font-size: 0.8rem; color: #999; margin-top: 8px;">
        <span style="color: #28a745; font-weight: 600;">{{ $stats['total_received'] }}</span> reçues · 
        <span style="color: #ffc107; font-weight: 600;">{{ $stats['total_pending'] }}</span> en attente
      </div>
    </div>

    <div class="proc-kpi-card">
      <div class="proc-kpi-label">❌ Réceptions Rejetées</div>
      <div class="proc-kpi-value" style="color: #dc3545;">{{ $stats['total_rejected'] }}</div>
      <div style="font-size: 0.8rem; color: #999; margin-top: 8px;">
        {{ number_format(($stats['total_rejected'] / max(1, $stats['total_receptions'])) * 100, 1) }}% du total
      </div>
    </div>

    <!-- Recipes Stats -->
    <div class="proc-kpi-card" style="border-left-color: #0066cc;">
      <div class="proc-kpi-label">🧪 Recettes Totales</div>
      <div class="proc-kpi-value" style="color: #0066cc;">{{ $stats['total_recipes'] }}</div>
      <div style="font-size: 0.8rem; color: #999; margin-top: 8px;">
        <span style="color: #0066cc; font-weight: 600;">{{ $stats['active_recipes'] }}</span> actives
      </div>
    </div>

    <!-- Production Stats -->
    <div class="proc-kpi-card" style="border-left-color: #ff6b35;">
      <div class="proc-kpi-label">🏭 Lots de Production</div>
      <div class="proc-kpi-value" style="color: #ff6b35;">{{ $stats['total_production_lots'] }}</div>
      <div style="font-size: 0.8rem; color: #999; margin-top: 8px;">
        {{ $stats['production_this_month'] }} ce mois
      </div>
    </div>

    <div class="proc-kpi-card" style="border-left-color: #ff6b35;">
      <div class="proc-kpi-label">📊 Quantité Produite</div>
      <div class="proc-kpi-value" style="color: #ff6b35;">
        {{ number_format($stats['total_quantity_produced'], 0) }}
      </div>
      <div style="font-size: 0.8rem; color: #999; margin-top: 8px;">kg au total</div>
    </div>
  </div>

  <!-- Tabs Navigation -->
  <div class="proc-tabs">
    @foreach($tabs as $key => [$label, $icon, $count])
      <button class="proc-tab {{ $loop->first ? 'active' : '' }}" onclick="switchTab('{{ $key }}')">
        {{ $label }} <span style="background: #e0e0e0; color: #666; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 8px; font-weight: 700;">{{ $count }}</span>
      </button>
    @endforeach
  </div>

  <!-- Tab 1: Matières Reçues -->
  <div id="receptions" class="proc-tab-pane active">
    <div class="proc-table-container">
      @if($receptions->isEmpty())
        <div class="proc-empty-state">
          <i class="fa-solid fa-inbox"></i>
          <p><strong>Aucune réception enregistrée</strong></p>
          <p style="font-size: 0.9rem;">Les transformateurs pourront enregistrer des réceptions une fois approuvés.</p>
        </div>
      @else
        <table class="proc-table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Transformateur</th>
              <th>Producteur</th>
              <th>Quantité</th>
              <th>Date Réception</th>
              <th>Qualité</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @foreach($receptions as $reception)
              <tr>
                <td>
                  <strong>{{ $reception->harvest->product_name ?? 'N/A' }}</strong>
                  @if($reception->harvest?->category)
                    <br><span style="font-size: 0.8rem; color: #999;">{{ $reception->harvest->category->name }}</span>
                  @endif
                </td>
                <td>
                  <div class="proc-company">
                    <div class="proc-company-avatar">
                      {{ strtoupper(substr($reception->processor->company_name ?? 'N', 0, 1)) }}
                    </div>
                    <div class="proc-company-info">
                      <h4>{{ $reception->processor->company_name ?? 'N/A' }}</h4>
                      <p>{{ $reception->processor->tax_id ?? '—' }}</p>
                    </div>
                  </div>
                </td>
                <td>
                  <small>{{ $reception->harvest?->farm->user->company_name ?? 'N/A' }}</small>
                </td>
                <td>
                  <strong>{{ number_format($reception->quantity_received, 2) }}</strong> {{ $reception->unit ?? 'kg' }}
                </td>
                <td>
                  <small>{{ $reception->reception_date?->format('d/m/Y H:i') ?? 'N/A' }}</small>
                </td>
                <td>
                  @php
                    $qualityMap = [
                      'good' => ['Bonne', 'proc-badge-received'],
                      'acceptable' => ['Acceptable', 'proc-badge-pending'],
                      'damaged' => ['Endommagée', 'proc-badge-rejected'],
                    ];
                    [$label, $class] = $qualityMap[$reception->quality_status] ?? ['—', ''];
                  @endphp
                  <span class="proc-badge {{ $class }}">{{ $label }}</span>
                </td>
                <td>
                  @php
                    $statusMap = [
                      'pending' => ['En attente', 'proc-badge-pending'],
                      'received' => ['Reçue', 'proc-badge-received'],
                      'rejected' => ['Rejetée', 'proc-badge-rejected'],
                    ];
                    [$label, $class] = $statusMap[$reception->status] ?? ['—', ''];
                  @endphp
                  <span class="proc-badge {{ $class }}">{{ $label }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <!-- Pagination -->
        @if($receptions->hasPages())
          <div class="proc-pagination">
            @if($receptions->onFirstPage())
              <span class="disabled">← Précédent</span>
            @else
              <a href="{{ $receptions->previousPageUrl() }}">← Précédent</a>
            @endif

            @foreach($receptions->getUrlRange(1, $receptions->lastPage()) as $page => $url)
              @if($page == $receptions->currentPage())
                <span class="active">{{ $page }}</span>
              @else
                <a href="{{ $url }}">{{ $page }}</a>
              @endif
            @endforeach

            @if($receptions->hasMorePages())
              <a href="{{ $receptions->nextPageUrl() }}">Suivant →</a>
            @else
              <span class="disabled">Suivant →</span>
            @endif
          </div>
        @endif
      @endif
    </div>
  </div>

  <!-- Tab 2: Recettes Registrées -->
  <div id="recipes" class="proc-tab-pane">
    <div class="proc-table-container">
      @if($recipes->isEmpty())
        <div class="proc-empty-state">
          <i class="fa-solid fa-flask"></i>
          <p><strong>Aucune recette enregistrée</strong></p>
          <p style="font-size: 0.9rem;">Les transformateurs créeront leurs recettes une fois approuvés et ayant reçu de la matière première.</p>
        </div>
      @else
        <table class="proc-table">
          <thead>
            <tr>
              <th>Nom de la Recette</th>
              <th>Transformateur</th>
              <th>Nombre d'Ingrédients</th>
              <th>Éco-Score IA</th>
              <th>Statut</th>
              <th>Date Création</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recipes as $recipe)
              <tr>
                <td>
                  <strong>{{ $recipe->name }}</strong>
                  @if($recipe->description)
                    <br><span style="font-size: 0.8rem; color: #999;">{{ Str::limit($recipe->description, 50) }}</span>
                  @endif
                </td>
                <td>
                  <div class="proc-company">
                    <div class="proc-company-avatar">
                      {{ strtoupper(substr($recipe->processor->company_name ?? 'N', 0, 1)) }}
                    </div>
                    <div class="proc-company-info">
                      <h4>{{ $recipe->processor->company_name ?? 'N/A' }}</h4>
                      <p>{{ $recipe->processor->tax_id ?? '—' }}</p>
                    </div>
                  </div>
                </td>
                <td>
                  <strong>{{ $recipe->items->count() }}</strong>
                </td>
                <td>
                  @php
                    $ecoScore = $recipe->eco_score ?? 'B';
                    $ecoClass = match($ecoScore) {
                      'A' => 'proc-badge-high-eco',
                      'B' => 'proc-badge-medium-eco',
                      'C' => 'proc-badge-low-eco',
                      default => 'proc-badge-medium-eco',
                    };
                  @endphp
                  <span class="proc-badge {{ $ecoClass }}">{{ $ecoScore }}</span>
                </td>
                <td>
                  @if($recipe->is_active)
                    <span class="proc-badge proc-badge-active">✓ Actif</span>
                  @else
                    <span class="proc-badge proc-badge-inactive">✕ Inactif</span>
                  @endif
                </td>
                <td>
                  <small>{{ $recipe->created_at?->format('d/m/Y') ?? 'N/A' }}</small>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <!-- Pagination -->
        @if($recipes->hasPages())
          <div class="proc-pagination">
            @if($recipes->onFirstPage())
              <span class="disabled">← Précédent</span>
            @else
              <a href="{{ $recipes->url(1) }}&recipes_page={{ $recipes->currentPage() - 1 }}">← Précédent</a>
            @endif

            @foreach($recipes->getUrlRange(1, $recipes->lastPage()) as $page => $url)
              @if($page == $recipes->currentPage())
                <span class="active">{{ $page }}</span>
              @else
                <a href="{{ $url }}&recipes_page={{ $page }}">{{ $page }}</a>
              @endif
            @endforeach

            @if($recipes->hasMorePages())
              <a href="{{ $recipes->url(1) }}&recipes_page={{ $recipes->currentPage() + 1 }}">Suivant →</a>
            @else
              <span class="disabled">Suivant →</span>
            @endif
          </div>
        @endif
      @endif
    </div>
  </div>

  <!-- Tab 3: Production & QR Codes -->
  <div id="production" class="proc-tab-pane">
    <div class="proc-table-container">
      @if($productionLots->isEmpty())
        <div class="proc-empty-state">
          <i class="fa-solid fa-qrcode"></i>
          <p><strong>Aucun lot de production enregistré</strong></p>
          <p style="font-size: 0.9rem;">Les transformateurs lanceront leurs productions une fois des recettes créées et matières premières validées.</p>
        </div>
      @else
        <table class="proc-table">
          <thead>
            <tr>
              <th>Code Lot</th>
              <th>Produit Fini</th>
              <th>Recette</th>
              <th>Quantité Produite</th>
              <th>Date Production</th>
              <th>QR Code</th>
            </tr>
          </thead>
          <tbody>
            @foreach($productionLots as $lot)
              <tr>
                <td>
                  <strong style="font-family: monospace; background: #f0f5f2; padding: 4px 8px; border-radius: 4px;">{{ $lot->batch_code ?? 'N/A' }}</strong>
                </td>
                <td>
                  <strong>{{ $lot->product_name ?? 'N/A' }}</strong>
                  @if($lot->description)
                    <br><span style="font-size: 0.8rem; color: #999;">{{ Str::limit($lot->description, 50) }}</span>
                  @endif
                </td>
                <td>
                  <small>{{ $lot->recipe->name ?? 'N/A' }}</small>
                </td>
                <td>
                  <strong>{{ number_format($lot->quantity_produced, 2) }}</strong> {{ $lot->unit ?? 'kg' }}
                </td>
                <td>
                  <small>{{ $lot->production_date?->format('d/m/Y H:i') ?? 'N/A' }}</small>
                </td>
                <td>
                  @if($lot->id)
                    <a href="{{ route('pro.processor.production-lots.qr-code', $lot) }}" class="proc-qr-link" target="_blank">
                      <i class="fa-solid fa-qrcode"></i> Voir QR
                    </a>
                  @else
                    <span style="color: #ccc; font-size: 0.85rem;">—</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <!-- Pagination -->
        @if($productionLots->hasPages())
          <div class="proc-pagination">
            @if($productionLots->onFirstPage())
              <span class="disabled">← Précédent</span>
            @else
              <a href="{{ $productionLots->url(1) }}&production_page={{ $productionLots->currentPage() - 1 }}">← Précédent</a>
            @endif

            @foreach($productionLots->getUrlRange(1, $productionLots->lastPage()) as $page => $url)
              @if($page == $productionLots->currentPage())
                <span class="active">{{ $page }}</span>
              @else
                <a href="{{ $url }}&production_page={{ $page }}">{{ $page }}</a>
              @endif
            @endforeach

            @if($productionLots->hasMorePages())
              <a href="{{ $productionLots->url(1) }}&production_page={{ $productionLots->currentPage() + 1 }}">Suivant →</a>
            @else
              <span class="disabled">Suivant →</span>
            @endif
          </div>
        @endif
      @endif
    </div>
  </div>

  <!-- Tab Switch Script -->
  <script>
    function switchTab(tabName) {
      // Hide all panes
      document.querySelectorAll('.proc-tab-pane').forEach(pane => {
        pane.classList.remove('active');
      });

      // Remove active class from all tabs
      document.querySelectorAll('.proc-tab').forEach(tab => {
        tab.classList.remove('active');
      });

      // Show selected pane
      document.getElementById(tabName).classList.add('active');

      // Mark tab as active
      event.target.classList.add('active');
    }
  </script>

@endsection
