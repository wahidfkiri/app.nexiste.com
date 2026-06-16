# Nexus CRM Desktop Wrapper

Wrapper Tauri du CRM Laravel.

## Objectif

- ouvrir le CRM dans une vraie fenetre desktop
- preparer les deep links OAuth Google
- exposer un bridge desktop minimal au frontend Laravel
- permettre une distribution Windows en `MSI`

## Prerequis

- Node.js 22+
- npm 10+
- Rust toolchain installe sur la machine de build
- Visual Studio Build Tools avec `Desktop development with C++`
- WebView2 disponible sous Windows

## Environnement Rust local

Sur cette machine, le disque `C:` ne dispose presque plus d espace libre. Le toolchain Rust a donc ete prepare pour utiliser:

- `CARGO_HOME=D:\Rust\.cargo`
- `RUSTUP_HOME=D:\Rust\.rustup`

Les scripts racine appellent automatiquement:

- `npm run desktop:tauri:dev`
- `npm run desktop:tauri:build`
- `npm run desktop:tauri:build:no-bundle`

via [desktop/tauri/scripts/tauri-env.ps1](D:/My%20Project/My%20CRM/nexus-crm/desktop/tauri/scripts/tauri-env.ps1).

## Installation

Depuis la racine du repo:

```powershell
npm run desktop:tauri:install
```

Ou directement:

```powershell
cd "D:\My Project\My CRM\nexus-crm\desktop\tauri"
cmd /c npm.cmd install
```

## Developpement

Dans un terminal:

```powershell
cd "D:\My Project\My CRM\nexus-crm"
php artisan serve --host=127.0.0.1 --port=8000
```

Dans un second terminal:

```powershell
cd "D:\My Project\My CRM\nexus-crm"
npm run desktop:tauri:dev
```

## URL cible du CRM

Le bootstrap Tauri lit:

- `.env.development`
- `.env.production`

Variables:

```env
VITE_CRM_DESKTOP_URL=http://127.0.0.1:8000/login
VITE_CRM_AUTO_REDIRECT_MS=900
```

En production, remplace `https://crm.example.com/login` par ton vrai domaine.

## Capabilities Tauri

- `default.json`: permissions du bootstrap local
- `remote-crm.json`: permissions Tauri exposees aux pages Laravel chargees dans le shell

Par defaut, les URLs autorisees sont:

- `http://127.0.0.1:8000/*`
- `https://crm.example.com/*`
- `https://www.crm.example.com/*`

Adapte ces valeurs avant un build de production.

## Deep links

Scheme configure:

```text
nexuscrm://
```

Le frontend Laravel recoit les evenements via `window.NexusDesktop`.

## Build Windows

```powershell
cd "D:\My Project\My CRM\nexus-crm"
npm run desktop:tauri:build
```

Le fichier Windows specifique `src-tauri/tauri.windows.conf.json` force le mode `offlineInstaller` pour WebView2.

## Etat actuel

Le wrapper est genere et compile correctement sur cette machine.

Etat verifie le 28 avril 2026:

- `cargo` et `rustc` sont installes et fonctionnels via l environnement `D:\Rust`
- Visual Studio Build Tools avec C++ est pris en charge via `VsDevCmd`
- `npm run desktop:tauri:build:no-bundle` genere le binaire release
- `npm run desktop:tauri:build` genere l installeur MSI Windows

Sorties observees:

- `desktop/tauri/src-tauri/target/release/nexus-crm-desktop.exe`
- `desktop/tauri/src-tauri/target/release/bundle/msi/Nexus CRM_0.1.0_x64_en-US.msi`
