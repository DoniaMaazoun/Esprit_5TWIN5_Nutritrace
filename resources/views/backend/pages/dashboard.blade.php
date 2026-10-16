@extends('backend.layouts.app')

@section('title', 'Dashboard - Farm to Fork Admin')

@section('content')

<!-- Page Title Header -->
<div class="row page-title-header">
  <div class="col-12">
    <div class="page-header">
      <h4 class="page-title">Dashboard</h4>
      <div class="quick-link-wrapper w-100 d-md-flex flex-md-wrap">
        <ul class="quick-links">
          <li><a href="{{ route('backend.products.index') }}">Produits</a></li>
          <li><a href="{{ route('backend.farms.index') }}">Fermes</a></li>
          <li><a href="{{ route('backend.batches.index') }}">Traçabilité</a></li>
        </ul>
        <ul class="quick-links ml-auto">
          <li><a href="#">Paramètres</a></li>
          <li><a href="#">Rapports</a></li>
          <li><a href="#">Alertes</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>
<!-- Page Title Header Ends -->

<!-- Stats Cards -->
<div class="row">
  <div class="col-md-12 grid-margin">
    <div class="card">
      <div class="card-body">
        <div class="row">
          <!-- Total Produits -->
          <div class="col-lg-3 col-md-6">
            <div class="d-flex">
              <div class="wrapper">
                <h3 class="mb-0 font-weight-semibold">1,284</h3>
                <h5 class="mb-0 font-weight-medium text-primary">Produits Tracés</h5>
                <p class="mb-0 text-muted">+12 cette semaine</p>
              </div>
              <div class="wrapper my-auto ml-auto">
                <i class="mdi mdi-package text-primary" style="font-size: 2rem;"></i>
              </div>
            </div>
          </div>

          <!-- Total Fermes -->
          <div class="col-lg-3 col-md-6 mt-md-0 mt-4">
            <div class="d-flex">
              <div class="wrapper">
                <h3 class="mb-0 font-weight-semibold">156</h3>
                <h5 class="mb-0 font-weight-medium text-primary">Fermes Partenaires</h5>
                <p class="mb-0 text-muted">+8 ce mois</p>
              </div>
              <div class="wrapper my-auto ml-auto">
                <i class="mdi mdi-leaf text-primary" style="font-size: 2rem;"></i>
              </div>
            </div>
          </div>

          <!-- Lots en Traçabilité -->
          <div class="col-lg-3 col-md-6 mt-md-0 mt-4">
            <div class="d-flex">
              <div class="wrapper">
                <h3 class="mb-0 font-weight-semibold">438</h3>
                <h5 class="mb-0 font-weight-medium text-primary">Lots Actifs</h5>
                <p class="mb-0 text-muted">En chaîne de distribution</p>
              </div>
              <div class="wrapper my-auto ml-auto">
                <i class="mdi mdi-link text-primary" style="font-size: 2rem;"></i>
              </div>
            </div>
          </div>

          <!-- Certifications Vérifiées -->
          <div class="col-lg-3 col-md-6 mt-md-0 mt-4">
            <div class="d-flex">
              <div class="wrapper">
                <h3 class="mb-0 font-weight-semibold">234</h3>
                <h5 class="mb-0 font-weight-medium text-primary">Certifications</h5>
                <p class="mb-0 text-muted">Bio, Équitable, etc.</p>
              </div>
              <div class="wrapper my-auto ml-auto">
                <i class="mdi mdi-certificate text-primary" style="font-size: 2rem;"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Dashboard Content -->
<div class="row">
  <!-- Produits Récents -->
  <div class="col-md-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <h4 class="card-title mb-0">Produits Récemment Ajoutés</h4>
          <a href="{{ route('backend.products.index') }}"><small>Voir tout</small></a>
        </div>
        <p>Derniers produits inscrits dans la plateforme</p>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Produit</th>
                <th>Ferme</th>
                <th>Catégorie</th>
                <th>Éco-Score</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Tomates Biologiques</td>
                <td>Ferme des Roses</td>
                <td>Légumes</td>
                <td><span class="badge badge-success">A</span></td>
                <td>Actif</td>
              </tr>
              <tr>
                <td>Miel Bio</td>
                <td>Ruches du Soleil</td>
                <td>Miel</td>
                <td><span class="badge badge-success">A+</span></td>
                <td>Actif</td>
              </tr>
              <tr>
                <td>Lait Frais</td>
                <td>Ferme Verte</td>
                <td>Produits Laitiers</td>
                <td><span class="badge badge-warning">B</span></td>
                <td>En attente</td>
              </tr>
              <tr>
                <td>Œufs Fermiers</td>
                <td>Poules Heureuses</td>
                <td>Œufs</td>
                <td><span class="badge badge-success">A</span></td>
                <td>Actif</td>
              </tr>
              <tr>
                <td>Riz Complet</td>
                <td>Rizière Bio</td>
                <td>Céréales</td>
                <td><span class="badge badge-info">B+</span></td>
                <td>Actif</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Activités Récentes -->
  <div class="col-md-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Activités Récentes</h4>
        <div class="activity-feed">
          <div class="feed-item border-bottom pb-3 mb-3">
            <span class="d-block font-weight-semibold mb-1">Nouveau produit ajouté</span>
            <p class="text-muted text-sm mb-0">Tomates Biologiques - 2 heures</p>
          </div>
          <div class="feed-item border-bottom pb-3 mb-3">
            <span class="d-block font-weight-semibold mb-1">Lot certifié</span>
            <p class="text-muted text-sm mb-0">Miel Bio - Certification Bio - 5 heures</p>
          </div>
          <div class="feed-item border-bottom pb-3 mb-3">
            <span class="d-block font-weight-semibold mb-1">Ferme enregistrée</span>
            <p class="text-muted text-sm mb-0">Ferme Verte - 1 jour</p>
          </div>
          <div class="feed-item pb-3">
            <span class="d-block font-weight-semibold mb-1">Traçabilité mise à jour</span>
            <p class="text-muted text-sm mb-0">5 lots en transit vers distributeurs - 2 jours</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Alertes & Avertissements -->
<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <h4 class="card-title mb-0">Alertes & Avertissements</h4>
          <a href="#"><small>Gérer alertes</small></a>
        </div>
        <div class="mt-3">
          <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong><i class="mdi mdi-alert"></i> Greenwashing Détecté:</strong> Un produit avec label "Écologique" n'a pas de certification valide. À vérifier.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong><i class="mdi mdi-information"></i> Certificat Expirant:</strong> La certification Bio du produit "Riz Complet" expire dans 15 jours.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
