<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="{{ route('backend.dashboard') }}" class="nav-link">
        <div class="profile-image">
          <img class="img-xs rounded-circle" src="{{ asset('backend/images/faces/face8.jpg') }}" alt="profile image">
          <div class="dot-indicator bg-success"></div>
        </div>
        <div class="text-wrapper">
          <p class="profile-name">Admin</p>
          <p class="designation">Farm to Fork</p>
        </div>
      </a>
    </li>

    <li class="nav-item nav-category">Menu Principal</li>

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link @if(Route::currentRouteName() === 'backend.dashboard') active @endif" href="{{ route('backend.dashboard') }}">
        <i class="menu-icon typcn typcn-document-text"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>

    <!-- User Approvals -->
    <li class="nav-item">
      <a class="nav-link @if(Route::currentRouteName() === 'backend.users.approvals') active @endif" href="{{ route('backend.users.approvals') }}">
        <i class="menu-icon typcn typcn-user-add"></i>
        <span class="menu-title">Approbations Comptes Pro</span>
        @php
          $pendingCount = \App\Models\User::where('role', 'pro')->where('status', 'pending')->count();
        @endphp
        @if($pendingCount > 0)
          <span class="badge badge-danger float-right">{{ $pendingCount }}</span>
        @endif
      </a>
    </li>

    <!-- Produits & Catégories -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#products-menu" aria-expanded="false" aria-controls="products-menu">
        <i class="menu-icon typcn typcn-shopping-bag"></i>
        <span class="menu-title">Produits & Catégories</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="products-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.products.index') }}">Liste des Produits</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.products.create') }}">Ajouter Produit</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.categories.index') }}">Catégories</a>
          </li>
        </ul>
      </div>
    </li>

    <!-- Fermes -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#farms-menu" aria-expanded="false" aria-controls="farms-menu">
        <i class="menu-icon typcn typcn-th-large-outline"></i>
        <span class="menu-title">Fermes</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="farms-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.farms.index') }}">Liste des Fermes</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.farms.create') }}">Ajouter Ferme</a>
          </li>
        </ul>
      </div>
    </li>

    <!-- Traçabilité -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#traceability-menu" aria-expanded="false" aria-controls="traceability-menu">
        <i class="menu-icon typcn typcn-link"></i>
        <span class="menu-title">Traçabilité</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="traceability-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.batches.index') }}">Lots de Produits</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.history.index') }}">Historique Chaîne</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Analyser Traçabilité</a>
          </li>
        </ul>
      </div>
    </li>

    <!-- Certifications -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#certifications-menu" aria-expanded="false" aria-controls="certifications-menu">
        <i class="menu-icon typcn typcn-document-add"></i>
        <span class="menu-title">Certifications</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="certifications-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.certifications.index') }}">Certificats</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.labels.index') }}">Vérifier Labels</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.greenwashing.index') }}">Anti-Greenwashing</a>
          </li>
        </ul>
      </div>
    </li>

    <!-- Module 3 : Logistique & Carbone -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#logistics-menu" aria-expanded="false" aria-controls="logistics-menu">
        <i class="menu-icon typcn typcn-location-arrow-outline"></i>
        <span class="menu-title">Logistique & Carbone</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="logistics-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.logistics.shipments.index') }}">Expéditions</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('backend.logistics.transport-modes.index') }}">Facteurs d'émission</a>
          </li>
        </ul>
      </div>
    </li>

    <!-- Éco-Score -->
    <li class="nav-item">
      <a class="nav-link" href="{{ route('backend.ecoscore.index') }}">
        <i class="menu-icon typcn typcn-bell"></i>
        <span class="menu-title">Éco-Score</span>
      </a>
    </li>

  </ul>
</nav>
