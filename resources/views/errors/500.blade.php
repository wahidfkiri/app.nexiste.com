@extends('errors.layout')

@section('status_code', '500')
@section('icon', 'fa-triangle-exclamation')
@section('tone', 'danger')
@section('title', 'Une erreur est survenue')
@section('subtitle', 'Un incident technique nous empêche d’afficher cette page.')
@section('hint', 'Nos équipes ont été informées. Merci de réessayer dans quelques minutes.')
