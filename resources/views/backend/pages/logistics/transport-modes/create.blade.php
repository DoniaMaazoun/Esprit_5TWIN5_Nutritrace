@extends('backend.layouts.app')

@section('title', 'Nouveau mode de transport - Farm to Fork Admin')

@section('content')
<div class="row">
  <div class="col-md-12">
    <h2 class="page-title mb-4"><i class="mdi mdi-truck"></i> Nouveau mode de transport</h2>
  </div>
</div>

@include('backend.pages.logistics.partials.alerts')

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('backend.logistics.transport-modes.store') }}">
          @csrf
          @include('backend.pages.logistics.transport-modes._form')
          <button type="submit" class="btn btn-success"><i class="mdi mdi-check"></i> Enregistrer</button>
          <a href="{{ route('backend.logistics.transport-modes.index') }}" class="btn btn-light">Annuler</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
