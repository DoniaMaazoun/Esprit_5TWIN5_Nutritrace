@extends('pro.layouts.producer')

@section('title', 'Nouvelle exploitation | Farm to Fork')

@section('producer-content')
<div class="page-head">
  <div>
    <a href="{{ route('pro.farms.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Mes exploitations</a>
    <h1>Nouvelle exploitation</h1>
    <p>Les champs marqués <span class="text-danger">*</span> sont obligatoires.</p>
  </div>
</div>

@include('pro.farms._form', ['action' => route('pro.farms.store')])
@endsection
