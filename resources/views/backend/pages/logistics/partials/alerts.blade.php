{{-- Module 3 - Messages flash et erreurs de validation (back-office) --}}
@foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $class)
  @if(session($key))
    <div class="alert alert-{{ $class }} alert-dismissible fade show" role="alert">
      {{ session($key) }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif
@endforeach

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
