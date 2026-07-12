@extends('errors.layout')

@section('status_code', '401')
@section('icon', 'fa-lock')
@section('tone', 'warning')
@section('title', 'Connexion requise')
@section('subtitle', 'Vous devez être connecté pour accéder à cette page.')
@section('hint', 'Connectez-vous à votre compte, puis réessayez.')
