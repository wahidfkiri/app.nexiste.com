@extends('errors.layout')

@section('status_code', '422')
@section('icon', 'fa-circle-exclamation')
@section('tone', 'warning')
@section('title', 'Informations incorrectes')
@section('subtitle', 'Certaines informations envoyées ne sont pas valides.')
@section('hint', 'Corrigez les champs signalés, puis validez de nouveau.')
