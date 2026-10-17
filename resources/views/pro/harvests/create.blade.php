@extends('pro.layouts.producer')

@section('title', 'Nouvelle récolte | Farm to Fork')

@section('producer-content')
<div class="page-head">
  <div>
    <a href="{{ route('pro.harvests.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Mes récoltes</a>
    <h1>Déclarer une récolte</h1>
    <p>Un code de lot unique et son QR code de traçabilité seront générés automatiquement.</p>
  </div>
</div>

@include('pro.harvests._form', ['action' => route('pro.harvests.store')])
@endsection
