@foreach(['success' => 'check-circle', 'error' => 'alert-circle'] as $type => $icon)
  @if(session($type))
    <div class="alert alert-{{ $type === 'error' ? 'danger' : 'success' }} alert-dismissible fade show" role="alert">
      <i class="mdi mdi-{{ $icon }}"></i> {{ session($type) }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
    </div>
  @endif
@endforeach
