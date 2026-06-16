@extends('layouts.global')

@section('title', 'Erreur 404')

@section('breadcrumb')
  <a href="{{ url('/dashboard') }}">Tableau de bord</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Erreur 404</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Page introuvable</h1>
    <p>La page demandee n'existe pas ou a ete deplacee.</p>
  </div>
  <div class="page-header-actions">
    <span class="badge badge-inactif"><i class="fas fa-circle-exclamation"></i> 404</span>
  </div>
</div>

<div class="info-card" style="max-width:880px;">
  <div class="info-card-header">
    <i class="fas fa-map-signs"></i>
    <h3>Impossible d'afficher cette page</h3>
  </div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      Verifiez l'adresse URL ou revenez a la page precedente.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ url()->previous() }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
      </a>
      <a href="{{ url('/dashboard') }}" class="btn btn-primary">
        <i class="fas fa-house"></i> Aller au tableau de bord
      </a>
    </div>
  </div>
</div>
@endsection
