@extends('frontend.layouts.app')

@section('title', 'Réception des Matières Brutes - Transformateur | Farm to Fork')

@section('extra-css')
<style>
  .processor-section {
    background: linear-gradient(135deg, #f0f9f7 0%, #e8f5f3 100%);
    padding-top: 40px;
    padding-bottom: 40px;
    min-height: 100vh;
  }

  .section-header {
    background: linear-gradient(135deg, #fd7e14 0%, #ff9a3d 100%);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(253, 126, 20, 0.2);
  }

  .section-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
    border-left: 4px solid #fd7e14;
  }

  .stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #fd7e14;
  }

  .stat-label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 0.5rem;
  }

  .harvest-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
  }

  .harvest-card:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
  }

  .harvest-info h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e3a2b;
    margin-bottom: 0.5rem;
  }

  .harvest-meta {
    display: flex;
    gap: 1.5rem;
    font-size: 0.9rem;
    color: #666;
  }

  .harvest-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-receive {
    background: #fd7e14;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
    text-decoration: none;
    display: inline-block;
  }

  .btn-receive:hover {
    background: #e67e0f;
    color: white;
  }

  .reception-list {
    background: white;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }

  .reception-table {
    width: 100%;
    border-collapse: collapse;
  }

  .reception-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    color: #1e3a2b;
    border-bottom: 2px solid #e0e0e0;
  }

  .reception-table td {
    padding: 1rem;
    border-bottom: 1px solid #e0e0e0;
  }

  .reception-table tr:hover {
    background: #f8f9fa;
  }

  .status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 0.25rem;
    font-size: 0.85rem;
    font-weight: 600;
  }

  .status-pending {
    background: #fff3cd;
    color: #856404;
  }

  .status-received {
    background: #d4edda;
    color: #155724;
  }

  .status-rejected {
    background: #f8d7da;
    color: #721c24;
  }

  .action-links {
    display: flex;
    gap: 0.75rem;
  }

  .btn-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
    border-radius: 0.25rem;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .btn-view {
    background: #0d6efd;
    color: white;
  }

  .btn-view:hover {
    background: #0b5ed7;
  }

  .btn-edit {
    background: #0dcaf0;
    color: white;
  }

  .btn-edit:hover {
    background: #31d5f8;
  }

  .empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
  }

  .empty-state i {
    font-size: 3rem;
    color: #ccc;
    margin-bottom: 1rem;
  }

  .tabs {
    display: flex;
    gap: 0;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e0e0e0;
  }

  .tab {
    padding: 1rem 1.5rem;
    cursor: pointer;
    border: none;
    background: none;
    font-weight: 600;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
  }

  .tab.active {
    color: #fd7e14;
    border-bottom-color: #fd7e14;
  }

  .tab-content {
    display: none;
  }

  .tab-content.active {
    display: block;
  }
</style>
@endsection

@section('content')

<div class="processor-section">
  <div class="container">
    <!-- Header -->
    <div class="section-header">
      <h1><i class="fa-solid fa-inbox"></i> Réception des Matières Brutes</h1>
      <p>Enregistrez la réception des lots du producteur à votre usine de transformation</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-number">{{ $stats['total_pending'] }}</div>
        <div class="stat-label">En attente</div>
      </div>
      <div class="stat-card">
        <div class="stat-number">{{ $stats['total_received'] }}</div>
        <div class="stat-label">Reçues</div>
      </div>
      <div class="stat-card">
        <div class="stat-number">{{ $stats['total_rejected'] }}</div>
        <div class="stat-label">Rejetées</div>
      </div>
      <div class="stat-card">
        <div class="stat-number">{{ number_format($stats['total_kg_received'], 0) }} kg</div>
        <div class="stat-label">Total reçu</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab active" onclick="switchTab('available')">
        <i class="fa-solid fa-plus-circle"></i> Lots Disponibles
      </button>
      <button class="tab" onclick="switchTab('receptions')">
        <i class="fa-solid fa-history"></i> Historique des Réceptions
      </button>
    </div>

    <!-- TAB 1: Available Harvests -->
    <div id="available" class="tab-content active">
      @if($availableHarvests->isEmpty())
        <div class="empty-state">
          <i class="fa-solid fa-inbox"></i>
          <h3>Aucun lot disponible</h3>
          <p>Les producteurs n'ont pas encore créé de lots validés</p>
        </div>
      @else
        <div>
          <h3 style="margin-bottom: 1.5rem; color: #1e3a2b;">Lots Disponibles pour Réception</h3>
          @foreach($availableHarvests as $harvest)
            <div class="harvest-card">
              <div class="harvest-info">
                <h3>{{ $harvest->name }}</h3>
                <div class="harvest-meta">
                  <span>
                    <i class="fa-solid fa-farm"></i>
                    {{ $harvest->farm->name }}
                  </span>
                  <span>
                    <i class="fa-solid fa-user"></i>
                    {{ $harvest->farm->user->name }}
                  </span>
                  <span>
                    <i class="fa-solid fa-calendar"></i>
                    {{ $harvest->harvest_date?->format('d/m/Y') ?? 'N/A' }}
                  </span>
                  <span>
                    <i class="fa-solid fa-weight"></i>
                    {{ $harvest->quantity_kg ?? 0 }} kg
                  </span>
                  <span>
                    <i class="fa-solid fa-tag"></i>
                    <span class="badge bg-success">{{ $harvest->category->name ?? 'N/A' }}</span>
                  </span>
                </div>
              </div>
              <a href="{{ route('pro.processor.raw-materials.create', $harvest) }}" class="btn-receive">
                <i class="fa-solid fa-arrow-right"></i> Enregistrer la Réception
              </a>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <!-- TAB 2: Reception History -->
    <div id="receptions" class="tab-content">
      @if($receptions->isEmpty())
        <div class="empty-state">
          <i class="fa-solid fa-history"></i>
          <h3>Aucune réception enregistrée</h3>
          <p>Commencez par enregistrer une réception de lot</p>
        </div>
      @else
        <div class="reception-list">
          <table class="reception-table">
            <thead>
              <tr>
                <th>Produit</th>
                <th>Producteur</th>
                <th>Quantité Reçue</th>
                <th>Date Réception</th>
                <th>Qualité</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($receptions as $reception)
                <tr>
                  <td>
                    <strong>{{ $reception->harvest->name }}</strong><br>
                    <small style="color: #666;">{{ $reception->harvest->category->name ?? 'N/A' }}</small>
                  </td>
                  <td>{{ $reception->harvest->farm->user->name }}</td>
                  <td>{{ number_format($reception->quantity_received, 2) }} {{ $reception->unit }}</td>
                  <td>{{ $reception->reception_date->format('d/m/Y H:i') }}</td>
                  <td>
                    <span class="badge" style="background: {{ $reception->quality_status === 'good' ? '#28a745' : ($reception->quality_status === 'acceptable' ? '#ffc107' : '#dc3545') }}; color: white;">
                      {{ $reception->getQualityLabel() }}
                    </span>
                  </td>
                  <td>
                    <span class="status-badge status-{{ $reception->status }}">
                      {{ $reception->getStatusLabel() }}
                    </span>
                  </td>
                  <td>
                    <div class="action-links">
                      <a href="{{ route('pro.processor.raw-materials.show', $reception) }}" class="btn-link btn-view">
                        <i class="fa-solid fa-eye"></i> Voir
                      </a>
                      @if($reception->isPending())
                        <a href="{{ route('pro.processor.raw-materials.edit', $reception) }}" class="btn-link btn-edit">
                          <i class="fa-solid fa-edit"></i> Modifier
                        </a>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 2rem;">
          {{ $receptions->links('pagination::bootstrap-5') }}
        </div>
      @endif
    </div>
  </div>
</div>

<script>
  function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
      tab.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(btn => {
      btn.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.closest('.tab').classList.add('active');
  }
</script>

@endsection
