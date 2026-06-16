# Google Gmail Realtime

## Objectif

Documenter le mode temps réel de l'extension `google-gmail`.

Le module repose sur une architecture hybride :

- `Socket.IO` pour pousser les changements vers l'interface
- une commande Laravel planifiée pour détecter les nouveaux emails entrants
- un fallback AJAX si le socket n'est pas disponible

## Ce qui est en place

### 1. Socket côté interface

Quand le socket est actif :

- les actions locales Gmail sont poussées en direct
- l'interface n'a plus besoin de recharger la page
- les statuts et la liste peuvent se mettre à jour sans refresh manuel

Actions déjà diffusées :

- envoi
- réponse
- transfert
- lu / non lu
- favori
- archive
- corbeille
- suppression

### 2. Détection serveur des emails entrants

Les nouveaux emails entrants ne sont pas encore pilotés par `Gmail Watch / History API / PubSub`.

À la place :

- Laravel exécute une synchro côté serveur
- si la boîte a changé, un événement Socket.IO est émis
- le front met alors à jour son snapshot utile

## Optimisations déjà appliquées

### Polling de secours réduit

Le fallback ne multiplie plus les appels séparés pour :

- `settings`
- `labels`
- `stats`
- `messages`

Il utilise maintenant un seul endpoint `snapshot` par cycle de secours.

### Refresh discret

Les refresh automatiques :

- ne déclenchent plus de loader visuel agressif
- ne mettent plus le bouton manuel en mode loading
- se font en arrière-plan autant que possible

## Fichiers impliqués

### Backend Laravel

- `extensions/google-gmail/src/Services/GoogleGmailService.php`
- `extensions/google-gmail/src/Services/GoogleGmailSocketService.php`
- `extensions/google-gmail/src/Http/Controllers/GoogleGmailController.php`
- `extensions/google-gmail/src/Console/Commands/GoogleGmailSyncRealtimeCommand.php`
- `extensions/google-gmail/config/google-gmail.php`
- `app/Console/Kernel.php`

### Front Gmail

- `extensions/google-gmail/src/Resources/assets/js/google-gmail.js`
- `public/vendor/google-gmail/js/google-gmail.js`
- `extensions/google-gmail/src/Resources/views/gmail/index.blade.php`
- `extensions/google-gmail/src/Resources/views/layouts/gmail.blade.php`

### Serveur Node

- `extensions/google-gmail/socket-server/server.js`
- `extensions/google-gmail/socket-server/package.json`

## Variables `.env`

```dotenv
GOOGLE_GMAIL_SOCKET_IO_ENABLED=true
GOOGLE_GMAIL_SOCKET_IO_URL=http://127.0.0.1:6004
GOOGLE_GMAIL_SOCKET_IO_PATH=/socket.io
GOOGLE_GMAIL_SOCKET_IO_NAMESPACE=/
GOOGLE_GMAIL_SOCKET_IO_EMIT_URL=http://127.0.0.1:6004/emit
GOOGLE_GMAIL_SOCKET_IO_SERVER_TOKEN=
GOOGLE_GMAIL_SOCKET_IO_SCHEDULER_ENABLED=true
GOOGLE_GMAIL_SOCKET_IO_PREVIEW_LIMIT=25
```

## Lancer le serveur Socket.IO Gmail

```bash
cd extensions/google-gmail/socket-server
npm install
npm start
```

Port par défaut :

- `6004`

## Scheduler Laravel

Commande :

```bash
php artisan google-gmail:sync-realtime
```

Le scheduler global exécute actuellement :

- `drafts:remind` toutes les heures
- `drafts:cleanup` tous les jours à `02:30`
- `google-gmail:sync-realtime` chaque minute

Vérification :

```bash
php artisan schedule:list
```

## Fonctionnement réel

### Chargement initial

- le front charge un premier état utile
- puis il tente une connexion Socket.IO

### Si le socket est disponible

- le polling de secours n'est plus utilisé
- les événements Gmail arrivent à chaud

### Si le socket échoue

- le front repasse en fallback
- chaque cycle de secours fait un seul appel `snapshot`
- l'interface reste utilisable

## Événements diffusés

Exemples :

- `connected`
- `disconnected`
- `settings.updated`
- `mailbox.synced`
- `message.sent`
- `message.replied`
- `message.forwarded`
- `message.updated`
- `message.deleted`

## Auth expirée

Le module Gmail gère aussi les sessions Google expirées ou révoquées :

- invalidation locale du token si nécessaire
- message clair de reconnexion
- reprise correcte si l'action venait d'une suggestion automation

Le moteur d'automation sait maintenant rouvrir la suggestion après reconnexion Gmail.

## Test rapide

Pré-requis :

- extension `google-gmail` active pour le tenant
- compte Gmail connecté
- serveur Socket.IO Gmail démarré
- scheduler Laravel actif

Scénario :

1. ouvrir l'extension Gmail
2. envoyer un email depuis le module
3. vérifier la mise à jour sans rechargement de page
4. envoyer un email vers cette boîte depuis un autre compte
5. attendre l'exécution du scheduler
6. vérifier l'apparition du nouveau message sans refresh manuel

## Troubleshooting

### La page continue à faire du polling

Vérifier :

- que le serveur Node répond bien
- que `GOOGLE_GMAIL_SOCKET_IO_ENABLED=true`
- que `GOOGLE_GMAIL_SOCKET_IO_URL` pointe vers le bon port
- que le client Socket.IO charge correctement côté page

### Les actions locales ne se propagent pas

Vérifier :

- `GOOGLE_GMAIL_SOCKET_IO_EMIT_URL`
- `GOOGLE_GMAIL_SOCKET_IO_SERVER_TOKEN`
- la santé du serveur `/health`

### Les nouveaux emails entrants ne remontent pas

Vérifier :

- que le scheduler tourne réellement
- que `schedule:list` contient `google-gmail:sync-realtime`
- que la connexion Gmail du tenant est toujours active

## Évolution possible

Pour aller plus loin vers un temps réel entrant plus natif :

- `Gmail Watch`
- `History API`
- `PubSub`

Dans ce cas, le scheduler pourrait rester un filet de sécurité au lieu d'être la source principale de détection.
