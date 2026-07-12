@extends('errors.layout')

@section('status_code', '429')
@section('icon', 'fa-hourglass-half')
@section('tone', 'warning')
@section('title', 'Trop de tentatives')
@section('subtitle', 'Vous avez effectué trop d’actions en peu de temps.')
@section('hint', 'Patientez quelques instants avant de réessayer.')
