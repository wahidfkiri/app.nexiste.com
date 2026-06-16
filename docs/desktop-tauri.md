# Tauri Desktop

Guide de démarrage et d'état pour le wrapper desktop Tauri de `nexus-crm`.

## Emplacement

- wrapper Tauri : `desktop/tauri`
- bridge Laravel / Tauri : `public/vendor/desktop/js/tauri-bridge.js`

## Objectif

Le wrapper Tauri permet de distribuer le CRM comme application desktop Windows sans réécrire le backend Laravel.

Le socle actuel couvre :

- wrapper Tauri séparé du code métier Laravel
- ouverture du CRM dans une fenêtre native
- deep link `nexuscrm://`
- `single-instance`
- notifications desktop
- base prête pour l'updater
- intégration OAuth desktop pour les flux Google et Notion via rebond Laravel

## Prérequis

- Node.js `22+`
- npm `10+`
- Rust toolchain (`cargo`, `rustc`)
- Visual Studio Build Tools avec `Desktop development with C++`
- CRM Laravel accessible localement pour le dev

## Scripts disponibles

Depuis la racine du repo :

```bash
npm run desktop:tauri:install
npm run desktop:tauri:dev
npm run desktop:tauri:build:no-bundle
npm run desktop:tauri:build
npm run desktop:tauri:frontend:build
```

## Lancement en développement

Terminal 1 :

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2 :

```bash
npm run desktop:tauri:dev
```

## Environnement Rust sur cette machine

Le script suivant prépare l'environnement avant les commandes Tauri :

- `desktop/tauri/scripts/tauri-env.ps1`

Il force notamment :

- `CARGO_HOME=D:\Rust\.cargo`
- `RUSTUP_HOME=D:\Rust\.rustup`

Cela a été mis en place pour éviter de saturer `C:` sur cette machine de dev.

## État actuel vérifié

Le wrapper est généré et branché dans le repo.

Points validés :

- installation npm Tauri OK
- build frontend Tauri OK
- build Rust / Tauri OK
- lancement dev Tauri OK
- production des artefacts Windows OK

Artefacts déjà générés :

- `desktop/tauri/src-tauri/target/release/nexus-crm-desktop.exe`
- `desktop/tauri/src-tauri/target/release/bundle/msi/Nexus CRM_0.1.0_x64_en-US.msi`

## OAuth desktop

Le projet embarque déjà une base pour les flux desktop :

- rebond OAuth Laravel -> deep link desktop
- support du login Google
- support du flux Gmail desktop
- base prête pour Notion Workspace côté desktop

Le principe est :

1. Laravel lance le flux OAuth
2. le provider revient sur un callback web Laravel
3. Laravel finalise l'échange et renvoie une page de rebond
4. la page de rebond notifie l'application desktop via deep link ou bridge

## Build Windows

### Sans bundle

```bash
npm run desktop:tauri:build:no-bundle
```

### Avec bundle MSI

```bash
npm run desktop:tauri:build
```

## À adapter avant diffusion réelle

- `desktop/tauri/.env.production`
- `desktop/tauri/src-tauri/capabilities/remote-crm.json`
- certificat de signature Windows
- configuration de l'updater
- URL finale du CRM si l'app doit ouvrir un domaine distant

## Limites actuelles

- l'updater est installé mais désactivé au runtime tant que son feed n'est pas configuré
- la qualité finale du flux dépendra encore des tests OAuth réels en environnement desktop complet
- la partie Store / signature Windows reste à industrialiser

## Recommandation pratique

Pour travailler sereinement :

1. stabiliser le CRM web et ses OAuth
2. tester les flux critiques dans Tauri
3. seulement ensuite industrialiser signature, mise à jour et distribution
