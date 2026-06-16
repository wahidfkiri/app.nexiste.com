@extends('errors.layout')

@section('status_code', '401')
@section('title', 'Authentification requise')
@section('subtitle', 'Cette action necessite une connexion valide.')
@section('hint', 'Connectez vous puis reessayez. Si le probleme persiste, contactez un administrateur.')
