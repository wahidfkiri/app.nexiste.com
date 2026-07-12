@extends('errors.layout')

@section('status_code', isset($code) ? (string) $code : (string) (($exception->getStatusCode() ?? 500)))
@section('icon', 'fa-triangle-exclamation')
@section('tone', 'neutral')
@section('title', 'Une erreur est survenue')
@section('subtitle', isset($message) && $message !== '' ? $message : 'La demande n’a pas pu être traitée pour le moment.')
@section('hint', 'Vous pouvez revenir à l’accueil ou à votre tableau de bord.')
