# Nexus CRM

CRM SaaS multi-tenant sous Laravel 10, orienté modules métier, marketplace interne, automatisations guidées, intégrations OAuth et wrapper desktop Tauri.

## Vue d'ensemble

Le projet couvre aujourd'hui :

- `Clients`, `Facturation`, `Stock`, `Utilisateurs`, `RBAC`
- `Projects`
- intégrations `Google Calendar`, `Google Drive`, `Google Sheets`, `Google Docs`, `Google Gmail`, `Google Meet`
- intégrations `Dropbox`, `Slack`, `Notion Workspace`, `Chatbot`
- moteur d'`automation` human-in-the-loop
- système de `drafts` avec autosave, reprise et rappels
- wrapper `desktop/tauri` prêt pour une application Windows
- environnement local HTTPS sous XAMPP / Apache sur `https://localhost`

## Prérequis

### Socle web

- PHP `8.2+`
- Laravel `10`
- MySQL `8+`
- Node.js `18+` pour le front web
- npm

### Compléments selon les usages

- Redis si vous voulez cache / session / queue hors mode fichier
- Node.js `22+` si vous utilisez le wrapper Tauri desktop
- Rust + Visual Studio Build Tools si vous compilez l'app Tauri Windows
- XAMPP / Apache si vous utilisez le setup HTTPS local du projet

## Installation rapide

### 1. Cloner le projet

```bash
git clone <URL_DU_REPO> nexus-crm
cd nexus-crm
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances front

```bash
npm install
```

### 4. Préparer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Variables minimales à renseigner dans `.env` :

- `APP_NAME`, `APP_ENV`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CACHE_DRIVER`, `SESSION_DRIVER`
- `MAIL_*`
- variables OAuth nécessaires selon les extensions activées

### 5. Base de données

```bash
php artisan migrate --seed
```

### 6. Lien de stockage public

```bash
php artisan storage:link
```

### 7. Build front

Développement :

```bash
npm run dev
```

Production :

```bash
npm run build
```

### 8. Démarrage Laravel simple

```bash
php artisan serve
```

### 9. Vérifications utiles

```bash
php artisan optimize:clear
php artisan about
php artisan route:list
```

## HTTPS local avec XAMPP / Apache

L'environnement local du projet est maintenant prévu pour fonctionner en HTTPS sur :

- [https://localhost](https://localhost)

Réglages importants côté `.env` :

```dotenv
APP_URL=https://localhost
SESSION_SECURE_COOKIE=true
```

Conséquences :

- les callbacks OAuth locaux doivent aussi être déclarés en `https://localhost/...`
- les cookies de session deviennent cohérents avec un usage sécurisé local
- si vous gardez certains sockets en `http://127.0.0.1:*`, pensez au risque de mixed content selon les pages

Guide dédié :

- [docs/local-https-xampp.md](docs/local-https-xampp.md)

## Configuration OAuth

### Google

URI locales typiques à déclarer dans Google Cloud Console :

- `https://localhost/auth/google/callback`
- `https://localhost/extensions/google-drive/oauth/callback`
- `https://localhost/extensions/google-calendar/oauth/callback`
- `https://localhost/extensions/google-sheets/oauth/callback`
- `https://localhost/extensions/google-docx/oauth/callback`
- `https://localhost/extensions/google-gmail/oauth/callback`
- `https://localhost/extensions/google-meet/oauth/callback`

Variables `.env` attendues :

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_AUTH_REDIRECT_URI=

GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REDIRECT_URI=

GOOGLE_CALENDAR_CLIENT_ID=
GOOGLE_CALENDAR_CLIENT_SECRET=
GOOGLE_CALENDAR_REDIRECT_URI=

GOOGLE_SHEETS_CLIENT_ID=
GOOGLE_SHEETS_CLIENT_SECRET=
GOOGLE_SHEETS_REDIRECT_URI=

GOOGLE_DOCX_CLIENT_ID=
GOOGLE_DOCX_CLIENT_SECRET=
GOOGLE_DOCX_REDIRECT_URI=

GOOGLE_GMAIL_CLIENT_ID=
GOOGLE_GMAIL_CLIENT_SECRET=
GOOGLE_GMAIL_REDIRECT_URI=

GOOGLE_MEET_CLIENT_ID=
GOOGLE_MEET_CLIENT_SECRET=
GOOGLE_MEET_REDIRECT_URI=
```

### Dropbox

Callback locale typique :

- `https://localhost/extensions/dropbox/oauth/callback`

Variables `.env` :

```dotenv
DROPBOX_CLIENT_ID=
DROPBOX_CLIENT_SECRET=
DROPBOX_REDIRECT_URI=/extensions/dropbox/oauth/callback
```

### Slack

Callback locale typique :

- `https://localhost/extensions/slack/oauth/callback`

Variables `.env` :

```dotenv
SLACK_CLIENT_ID=
SLACK_CLIENT_SECRET=
SLACK_REDIRECT_URI=/extensions/slack/oauth/callback
SLACK_API_BASE_URL=https://slack.com/api
```

### Notion Workspace

L'extension Notion utilise désormais la vraie API OAuth Notion, pas un workspace local custom.

Callback locale typique :

- `https://localhost/extensions/notion-workspace/oauth/callback`

Variables `.env` :

```dotenv
NOTION_WORKSPACE_CLIENT_ID=
NOTION_WORKSPACE_CLIENT_SECRET=
NOTION_WORKSPACE_REDIRECT_URI=/extensions/notion-workspace/oauth/callback
```

## Marketplace et applications

Le marketplace active les modules par tenant.

Commandes utiles :

```bash
php artisan extensions:seed
php artisan extensions:seed --reset
```

Applications qui demandent généralement une connexion externe après installation :

- Google Drive
- Google Calendar
- Google Sheets
- Google Docs
- Google Gmail
- Google Meet
- Dropbox
- Slack
- Notion Workspace

## Drafts et reprise de saisie

Le CRM embarque un système de brouillons multi-tenant.

Ce qu'il fait :

- autosave silencieux côté formulaire
- reprise d'un brouillon au retour utilisateur
- suppression du brouillon après validation finale réussie
- rappel différé uniquement si la saisie n'est pas terminée

Commandes planifiées :

```bash
php artisan schedule:list
```

Éléments attendus :

- `drafts:remind` toutes les heures
- `drafts:cleanup` tous les jours à `02:30`

## Automation intelligent

Le moteur d'automation fonctionne en mode human-in-the-loop :

- un événement métier déclenche des suggestions
- l'utilisateur accepte ou ignore
- si accepté, l'action réelle est exécutée
- en cas d'auth externe expirée, la suggestion peut être remise en attente avec demande de reconnexion

Cas déjà couverts :

- Gmail
- Google Calendar
- Google Drive
- Dropbox
- Slack
- Google Meet
- Google Sheets
- Google Docs
- Notion Workspace

Guide dédié :

- [docs/automation-system.md](docs/automation-system.md)

## Notion Workspace

L'extension `notion-workspace` est maintenant pensée comme une vraie intégration Notion :

- connexion OAuth officielle Notion
- recherche de pages Notion partagées
- lecture bloc par bloc depuis l'API Notion
- création de page dans le workspace connecté
- liaison CRM entre page Notion et client / projet
- suggestions automation pouvant ouvrir Notion ou créer une page Notion

## Temps réel Gmail

Le module Gmail utilise une architecture hybride :

- Socket.IO pour pousser les changements vers l'interface
- scheduler Laravel pour détecter les nouveaux emails entrants
- fallback polling avec un seul endpoint `snapshot` si le socket est indisponible

Guide dédié :

- [docs/google-gmail-realtime.md](docs/google-gmail-realtime.md)

## Desktop Tauri

Le repo contient un wrapper desktop Tauri dans `desktop/tauri`.

Scripts racine disponibles :

```bash
npm run desktop:tauri:install
npm run desktop:tauri:dev
npm run desktop:tauri:build:no-bundle
npm run desktop:tauri:build
npm run desktop:tauri:frontend:build
```

Guide dédié :

- [docs/desktop-tauri.md](docs/desktop-tauri.md)

## Queue, scheduler et temps réel

Par défaut, le projet peut tourner en mode simple avec :

```dotenv
QUEUE_CONNECTION=sync
CACHE_DRIVER=file
SESSION_DRIVER=file
```

Pour une exécution plus propre en arrière-plan :

- utilisez Redis ou `database`
- démarrez un worker queue
- laissez le scheduler Laravel tourner

Exemple :

```bash
php artisan queue:work --queue=default,automation --tries=3
php artisan schedule:work
```

## Déploiement rapide

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Si vous utilisez les intégrations temps réel ou desktop, prévoyez aussi :

- serveur Socket.IO concerné démarré
- scheduler Laravel actif
- queue worker actif si nécessaire
- certificats et variables OAuth cohérents avec le domaine final

## Documentation disponible

- [docs/README.md](docs/README.md)
- [docs/SCENARIOS_PROJET_CRM.md](docs/SCENARIOS_PROJET_CRM.md)
- [docs/automation-system.md](docs/automation-system.md)
- [docs/google-gmail-realtime.md](docs/google-gmail-realtime.md)
- [docs/desktop-tauri.md](docs/desktop-tauri.md)
- [docs/local-https-xampp.md](docs/local-https-xampp.md)
- [docs/validation-security.md](docs/validation-security.md)

## Troubleshooting rapide

### Erreur OAuth locale

Vérifier :

- le protocole `https://localhost`
- la même callback exacte dans `.env` et dans la console du provider
- que l'extension est bien activée pour le tenant

### Les suggestions automation échouent après expiration d'un token

Vérifier :

- que l'utilisateur reconnecte bien l'extension concernée
- que la suggestion repasse en `pending`
- qu'une notification de reprise apparaît dans le header

### Gmail reste en polling

Vérifier :

- que le serveur Socket.IO Gmail tourne
- que `GOOGLE_GMAIL_SOCKET_IO_ENABLED=true`
- que le scheduler exécute `google-gmail:sync-realtime`

### Les brouillons ne disparaissent pas après succès

Vérifier :

- que le formulaire transmet bien `draft_id`
- que le contrôleur appelle `DraftService::forgetFromRequest()` sur le flux concerné

