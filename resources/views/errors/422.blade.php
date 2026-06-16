@extends('errors.layout')

@section('status_code', '422')
@section('title', 'Donnees invalides')
@section('subtitle', 'La requete contient des donnees qui ne peuvent pas etre traitees.')
@section('hint', 'Corrigez les champs concernes puis soumettez de nouveau.')
