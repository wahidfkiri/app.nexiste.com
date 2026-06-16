@extends('errors.layout')

@section('status_code', '403')
@section('title', 'Acces refuse')
@section('subtitle', 'Vous n avez pas les permissions necessaires pour acceder a cette page.')
@section('hint', 'Verifiez votre role et vos droits, ou demandez l acces a un administrateur.')
