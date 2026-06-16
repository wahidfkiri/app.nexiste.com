@extends('errors.layout')

@section('status_code', isset($code) ? (string) $code : (string) (($exception->getStatusCode() ?? 500)))
@section('title', 'Erreur HTTP')
@section('subtitle', isset($message) && $message !== '' ? $message : 'La requete n a pas pu etre traitee.')
@section('hint', 'Vous pouvez revenir a l accueil ou au tableau de bord.')
