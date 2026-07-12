@extends('errors.layout')

@section('status_code', '403')
@section('icon', 'fa-ban')
@section('tone', 'warning')
@section('title', 'Accès non autorisé')
@section('subtitle', 'Vous n’avez pas les autorisations nécessaires pour consulter cette page.')
@section('hint', 'Si vous pensez qu’il s’agit d’une erreur, rapprochez-vous de votre administrateur.')
