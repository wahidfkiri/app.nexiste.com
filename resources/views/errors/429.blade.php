@extends('errors.layout')

@section('status_code', '429')
@section('title', 'Trop de requetes')
@section('subtitle', 'Vous avez effectue trop d actions en peu de temps.')
@section('hint', 'Patientez quelques instants avant de reessayer.')
