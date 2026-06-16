# Validation et sécurité (Laravel + AJAX)

## Objectif

Mettre en place une validation `serveur-first`, robuste, réutilisable et compatible avec :

- formulaires HTML classiques
- soumissions AJAX
- réponses JSON homogènes

## Composants en place

### 1. Sanitization d'entrée

- `app/Support/Security/InputSanitizer.php`
- suppression des caractères de contrôle
- nettoyage UTF-8
- neutralisation de patterns XSS courants
- support des tableaux imbriqués et fichiers

### 2. Middleware de sanitization

- `app/Http/Middleware/SanitizeInput.php`

Applique une sanitization douce sur toutes les requêtes.

### 3. Idempotence

- `app/Http/Middleware/EnsureIdempotency.php`

Évite les doubles soumissions via :

- `Idempotency-Key`
- `X-Request-Id`
- `_request_id`

Retour prévu :

- `409` si la requête a déjà été soumise récemment

### 4. Base FormRequest sécurisée

- `app/Http/Requests/SecureFormRequest.php`

Cette base ajoute :

- sanitization stricte avant validation
- format JSON standardisé pour les erreurs `422`

### 5. Front sécurisé

- `public/vendor/client/js/secure-form.js`

Le script :

- génère un `_request_id`
- envoie les headers utiles
- gère les erreurs de validation champ par champ côté front

## Contrôleurs déjà branchés

- `app/Http/Controllers/Auth/AuthController.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/SecurityValidationDemoController.php`

## Règles de sortie

### XSS

En Blade :

- utiliser `{{ $value }}`
- éviter `{!! !!}` sauf contenu explicitement maîtrisé

### SQL Injection

- utiliser Eloquent ou Query Builder avec bindings
- éviter les concaténations SQL brutes

## AJAX et UX

Le front doit toujours considérer le backend comme source de vérité :

- validation faite côté serveur
- erreurs rendues de façon claire
- double soumission évitée
- messages d'état propres pour l'utilisateur

## HTTPS local

Le projet fonctionne aussi en local sécurisé sur `https://localhost`.

Cela renforce la cohérence de :

- `SESSION_SECURE_COOKIE=true`
- callbacks OAuth locaux
- tests proches d'un vrai environnement sécurisé

Guide lié :

- [local-https-xampp.md](local-https-xampp.md)

