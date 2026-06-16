@extends('errors.layout')

@section('status_code', '502')
@section('title', 'Passerelle indisponible')
@section('subtitle', 'Le serveur en amont a renvoye une reponse invalide.')
@section('hint', 'Merci de patienter un instant puis de reessayer.')
