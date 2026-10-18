@extends('backend.layouts.app')

@section('title', 'Gestion des Approbations - Farm to Fork Admin')

@php
  $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $typeIcons = ['producer' => 'sprout', 'processor' => 'factory', 'distributor' => 'truck'];
  $tabs = [
      'pending' => ['En attente', 'clock-outline', $pendingUsers, '#b7791f'],
      'approved' => ['Approuvés', 'check-circle', $approvedUsers, '#2d5a27'],
      'rejected' => ['Rejetés', 'close-circle', $rejectedUsers, '#c53030'],
  ];
@endphp

@section('extra-css')
  @include('backend.pages.partials.module-styles')
  <style>
    .ap-tab-pane { display: none; } .ap-tab-pane.active { display: block; }
    .m1-tab { cursor: pointer; }
    .ap-type { display: inline-flex; align-items: center; gap: 5px; }
  </style>
@endsection

@section('content')
<div class="m1-head">
  <div>
    <div class="m1-crumb"><a href="{{ route('backend.dashboard') }}">Back-office</a> / Utilisateurs</div>
    <h3><i class="mdi mdi-account-check"></i> Comptes professionnels</h3>
    <p>Validez les producteurs, transformateurs et distributeurs avant qu'ils n'accèdent à leur espace.</p>
  </div>
</div>

@include('backend.pages.partials.flash')
@if(session('warning'))
  <div class="alert alert-warning"><i class="mdi mdi-alert"></i> {{ session('warning') }}</div>
@endif

<div class="m1-kpis">
  <div class="m1-kpi"><div class="m1-kpi__icon ic-amber"><i class="mdi mdi-clock-outline"></i></div><div><strong>{{ $pendingUsers->total() }}</strong><span>En attente</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-green"><i class="mdi mdi-check-circle"></i></div><div><strong>{{ $approvedUsers->total() }}</strong><span>Approuvés</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-red"><i class="mdi mdi-close-circle"></i></div><div><strong>{{ $rejectedUsers->total() }}</strong><span>Rejetés</span></div></div>
</div>

<div class="m1-tabs" role="tablist">
  @foreach($tabs as $key => [$label, $icon, $list])
    <a class="m1-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $key }}" role="tab"><i class="mdi mdi-{{ $icon }}"></i> {{ $label }} <span class="count">{{ $list->total() }}</span></a>
  @endforeach
</div>

@foreach($tabs as $key => [$label, $icon, $list, $color])
  <div class="m1-panel ap-tab-pane {{ $loop->first ? 'active' : '' }}" id="tab-{{ $key }}" role="tabpanel">
    @if($list->isEmpty())
      <div class="m1-empty"><i class="mdi mdi-{{ $key === 'pending' ? 'check-decagram' : 'account-off' }}"></i>
        {{ $key === 'pending' ? 'Aucun compte en attente : tout est traité.' : 'Aucun compte dans cette liste.' }}</div>
    @else
      <div class="table-responsive">
        <table class="table m1-table">
          <thead><tr><th>Professionnel</th><th>Activité</th><th>Entreprise</th><th>Matricule fiscal</th><th>{{ $key === 'pending' ? 'Inscription' : 'Mise à jour' }}</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
            @foreach($list as $user)
              <tr>
                <td>
                  <div class="m1-producer">
                    <span class="m1-avatar" style="background: {{ $color }}">{{ $initials($user->company_name ?: $user->name) }}</span>
                    <div class="m1-owner"><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div>
                  </div>
                </td>
                <td><span class="m1-chip ap-type"><i class="mdi mdi-{{ $typeIcons[$user->pro_type] ?? 'briefcase' }}"></i> {{ $user->getProTypeLabel() }}</span></td>
                <td>{{ $user->company_name }}</td>
                <td><code>{{ $user->tax_id }}</code></td>
                <td class="m1-sub">{{ ($key === 'pending' ? $user->created_at : $user->updated_at)->format('d/m/Y H:i') }}<br>{{ ($key === 'pending' ? $user->created_at : $user->updated_at)->diffForHumans() }}</td>
                <td class="text-right m1-actions">
                  @if($key === 'pending')
                    <form method="POST" action="{{ route('backend.users.approve', $user) }}" class="d-inline">
                      @csrf <button type="submit" class="btn btn-sm btn-success"><i class="mdi mdi-check"></i> Approuver</button>
                    </form>
                    <form method="POST" action="{{ route('backend.users.reject', $user) }}" class="d-inline" onsubmit="return confirm('Rejeter le compte de {{ addslashes($user->company_name) }} ?');">
                      @csrf <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-close"></i> Rejeter</button>
                    </form>
                  @elseif($key === 'rejected')
                    <form method="POST" action="{{ route('backend.users.reset-pending', $user) }}" class="d-inline">
                      @csrf <button type="submit" class="btn btn-sm btn-light"><i class="mdi mdi-restart"></i> Remettre en attente</button>
                    </form>
                  @else
                    @if($user->pro_type === 'producer')
                      <a href="{{ route('backend.farms.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-light"><i class="mdi mdi-barn"></i> Exploitations</a>
                      <a href="{{ route('backend.products.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-info"><i class="mdi mdi-basket"></i> Produits</a>
                    @else
                      <span class="m1-chip" style="background:#e6f6ec;color:#22663a"><i class="mdi mdi-check"></i> Actif</span>
                    @endif
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if($list->hasPages())
        <div class="m1-panel__foot"><span>{{ $list->total() }} compte(s)</span>{{ $list->links('pagination::bootstrap-4') }}</div>
      @endif
    @endif
  </div>
@endforeach
@endsection

@section('extra-js')
  <script>
    document.querySelectorAll('.m1-tab[data-tab]').forEach((tab) => tab.addEventListener('click', () => {
      document.querySelectorAll('.m1-tab[data-tab]').forEach((t) => t.classList.toggle('active', t === tab));
      document.querySelectorAll('.ap-tab-pane').forEach((p) => p.classList.toggle('active', p.id === 'tab-' + tab.dataset.tab));
    }));
  </script>
@endsection
