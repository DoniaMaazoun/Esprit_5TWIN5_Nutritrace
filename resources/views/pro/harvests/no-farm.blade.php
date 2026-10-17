@extends('pro.layouts.producer')

@section('title', 'Nouvelle récolte | Farm to Fork')

@section('producer-content')
<div class="p-card empty-state">
  <i class="fa-solid fa-wheat-awn d-block"></i>
  <h2 class="h5">Créez d'abord une exploitation</h2>
  <p>Chaque récolte doit être rattachée à une exploitation pour garantir son origine.</p>
  <a href="{{ route('pro.farms.create') }}" class="btn btn-success"><i class="fa-solid fa-plus"></i> Nouvelle exploitation</a>
</div>
@endsection
