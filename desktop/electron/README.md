# Nexiste CRM — Desktop (Electron)

Application desktop qui charge l'application web **https://app.nexiste.com** dans une
fenêtre native, avec :

- **Écran de chargement** (splash) affiché pendant le chargement de l'espace.
- **Écran hors-ligne** centré (« Pas de connexion Internet ») avec bouton **Réessayer**
  et relance automatique dès le retour de la connexion.
- Ouverture des liens externes (autre domaine) dans le navigateur système.
- Instance unique, menu masqué, `contextIsolation` activé (sécurisé).

## Structure

```
desktop/electron/
├─ main.js              # processus principal (fenêtre, splash, détection hors-ligne)
├─ preload.js           # pont sécurisé (bouton Réessayer)
├─ renderer/
│  ├─ splash.html       # écran de chargement
│  └─ offline.html      # écran « pas d'internet » + bouton Réessayer
├─ build/               # (optionnel) icon.ico pour l'exécutable
└─ package.json
```

## Développement

```bash
cd desktop/electron
npm install
npm start
```

Depuis la racine du projet :

```bash
npm run desktop:electron:install
npm run desktop:electron:dev
```

## Construire l'exécutable Windows (.exe)

```bash
cd desktop/electron
npm install
npm run build:win        # installeur NSIS -> desktop/electron/dist/*.exe
# ou
npm run build:portable   # .exe portable (sans installation)
```

Depuis la racine :

```bash
npm run desktop:electron:build
```

L'exécutable et l'installeur sont générés dans `desktop/electron/dist/`.

## Configuration

- **URL cible** : par défaut `https://app.nexiste.com`. Surchargeable via la variable
  d'environnement `NEXISTE_CRM_URL` au lancement.
- **Icône** : placez un fichier `build/icon.ico` (256×256 recommandé) pour personnaliser
  l'icône de l'application et de l'installeur. Sans icône, l'icône Electron par défaut
  est utilisée.

## Prérequis build Windows

- Node.js 18+ et npm.
- La compilation NSIS se fait automatiquement via `electron-builder` (télécharge les
  outils nécessaires au premier build). Construire l'`.exe` Windows depuis Windows.
