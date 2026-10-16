@extends('backend.layouts.app')

@section('title', 'Gestion des Approbations - Farm to Fork Admin')

@section('content')
<div class="content-wrapper">
  <div class="row">
    <div class="col-md-12">
      <div class="page-header d-print-none">
        <div class="row align-items-center">
          <div class="col">
            <h2 class="page-title">
              <i class="mdi mdi-account-check"></i> Gestion des Comptes Professionnels
            </h2>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Success & Error Messages -->
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="mdi mdi-check-circle"></i>
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <i class="mdi mdi-alert"></i>
      {{ session('warning') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="mdi mdi-alert-circle"></i>
      {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  <!-- Pending Approvals Tab -->
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="mdi mdi-clock"></i>
            En Attente d'Approbation
            <span class="badge badge-light float-right">{{ $pendingUsers->total() }}</span>
          </h5>
        </div>
        <div class="card-body">
          @if($pendingUsers->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Type d'Activité</th>
                    <th>Entreprise</th>
                    <th>Matricule</th>
                    <th>Date d'Inscription</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingUsers as $user)
                    <tr>
                      <td>
                        <strong>{{ $user->name }}</strong>
                      </td>
                      <td>{{ $user->email }}</td>
                      <td>
                        <span class="badge badge-info">{{ $user->getProTypeLabel() }}</span>
                      </td>
                      <td>{{ $user->company_name }}</td>
                      <td><code>{{ $user->tax_id }}</code></td>
                      <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                      <td>
                        <form method="POST" action="{{ route('backend.users.approve', $user) }}" style="display: inline;">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-success" title="Approuver">
                            <i class="mdi mdi-check"></i> Approuver
                          </button>
                        </form>
                        <form method="POST" action="{{ route('backend.users.reject', $user) }}" style="display: inline;">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-danger" title="Rejeter" onclick="return confirm('Êtes-vous sûr de vouloir rejeter ce compte?');">
                            <i class="mdi mdi-close"></i> Rejeter
                          </button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            {{ $pendingUsers->links() }}
          @else
            <div class="alert alert-info" role="alert">
              <i class="mdi mdi-information"></i>
              Aucun compte professionnel en attente d'approbation.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Approved Users Tab -->
  <div class="row mt-4">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0">
            <i class="mdi mdi-check-circle"></i>
            Comptes Approuvés
            <span class="badge badge-light float-right">{{ $approvedUsers->total() }}</span>
          </h5>
        </div>
        <div class="card-body">
          @if($approvedUsers->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Type d'Activité</th>
                    <th>Entreprise</th>
                    <th>Date d'Approbation</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($approvedUsers as $user)
                    <tr>
                      <td>
                        <strong>{{ $user->name }}</strong>
                      </td>
                      <td>{{ $user->email }}</td>
                      <td>
                        <span class="badge badge-success">{{ $user->getProTypeLabel() }}</span>
                      </td>
                      <td>{{ $user->company_name }}</td>
                      <td>{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                      <td>
                        <a href="#" class="btn btn-sm btn-info">
                          <i class="mdi mdi-eye"></i> Voir
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            {{ $approvedUsers->links() }}
          @else
            <div class="alert alert-info" role="alert">
              <i class="mdi mdi-information"></i>
              Aucun compte professionnel approuvé.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Rejected Users Tab -->
  <div class="row mt-4">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0">
            <i class="mdi mdi-close-circle"></i>
            Comptes Rejetés
            <span class="badge badge-light float-right">{{ $rejectedUsers->total() }}</span>
          </h5>
        </div>
        <div class="card-body">
          @if($rejectedUsers->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Type d'Activité</th>
                    <th>Entreprise</th>
                    <th>Date de Rejet</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($rejectedUsers as $user)
                    <tr>
                      <td>
                        <strong>{{ $user->name }}</strong>
                      </td>
                      <td>{{ $user->email }}</td>
                      <td>
                        <span class="badge badge-danger">{{ $user->getProTypeLabel() }}</span>
                      </td>
                      <td>{{ $user->company_name }}</td>
                      <td>{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                      <td>
                        <form method="POST" action="{{ route('backend.users.reset-pending', $user) }}" style="display: inline;">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-warning" title="Réinitialiser">
                            <i class="mdi mdi-restart"></i> Réinitialiser
                          </button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            {{ $rejectedUsers->links() }}
          @else
            <div class="alert alert-info" role="alert">
              <i class="mdi mdi-information"></i>
              Aucun compte professionnel rejeté.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

</div>

<style>
  .badge-light { background-color: #f8f9fa; color: #333; }
  .bg-primary { background-color: #007bff !important; }
  .bg-success { background-color: #28a745 !important; }
  .bg-danger { background-color: #dc3545 !important; }
  .text-white { color: white !important; }
  .btn-success { background-color: #28a745; border-color: #28a745; color: white; }
  .btn-success:hover { background-color: #218838; border-color: #1e7e34; }
  .btn-danger { background-color: #dc3545; border-color: #dc3545; color: white; }
  .btn-danger:hover { background-color: #c82333; border-color: #bd2130; }
  .btn-info { background-color: #17a2b8; border-color: #17a2b8; color: white; }
  .btn-info:hover { background-color: #138496; border-color: #117a8b; }
  .btn-warning { background-color: #ffc107; border-color: #ffc107; color: #333; }
  .btn-warning:hover { background-color: #e0a800; border-color: #d39e00; }
</style>
@endsection
