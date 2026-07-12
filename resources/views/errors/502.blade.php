@extends('errors.layout')

@section('status_code', '502')
@section('icon', 'fa-plug-circle-xmark')
@section('tone', 'danger')
@section('title', 'Service momentanément indisponible')
@section('subtitle', 'Nous rencontrons une difficulté temporaire pour joindre le service.')
@section('hint', 'Merci de patienter un instant, puis de réessayer.')
