@extends('errors.layout')

@section('status_code', '405')
@section('title', 'Methode non autorisee')
@section('subtitle', 'La methode HTTP utilisee n est pas autorisee pour cette ressource.')
@section('hint', 'Revenez a la page precedente et relancez l action correctement.')
