# HTTPS local avec XAMPP / Apache

Ce guide résume la configuration locale HTTPS utilisée pour ce projet sous Windows avec XAMPP.

## Objectif

Faire tourner le CRM en local sur :

- [https://localhost](https://localhost)

avec :

- certificat local accepté par Windows
- session sécurisée côté Laravel
- callbacks OAuth cohérents en HTTPS

## Réglages Laravel attendus

Dans `.env` :

```dotenv
APP_URL=https://localhost
SESSION_SECURE_COOKIE=true
```

Si vous utilisez OAuth en local, déclarez aussi vos callbacks en `https://localhost/...`.

## Réglages Apache attendus

Le principe est :

- un vhost HTTP qui redirige vers HTTPS
- un vhost HTTPS qui pointe vers `public/`
- un certificat local pour `localhost`

Exemple de cibles à adapter selon votre machine :

- `DocumentRoot` -> `D:/My Project/My CRM/nexus-crm/public`
- certificat -> `apache/conf/ssl.crt/*.crt`
- clé -> `apache/conf/ssl.key/*.key`

## Vérifications utiles

### Vérifier la syntaxe Apache

```bash
httpd -t
```

### Vérifier Laravel

```bash
php artisan optimize:clear
```

### Vérifier l'URL locale

- [https://localhost/login](https://localhost/login)

## Points d'attention OAuth

Si vous avez changé les callbacks locales vers `https://localhost`, il faut aussi les mettre à jour dans :

- Google Cloud Console
- Dropbox App Console
- Slack API
- Notion Integrations

Sinon les redirections OAuth seront refusées.

## Mixed content

Si une page HTTPS tente de charger un service temps réel en `http://127.0.0.1:*`, certains navigateurs peuvent refuser la requête.

À surveiller surtout pour :

- Slack Socket.IO
- Chatbot Socket.IO
- Google Gmail Socket.IO

## Bonnes pratiques

- garder `APP_URL` et les callbacks cohérents
- éviter de mélanger `http://127.0.0.1:8000` et `https://localhost` dans les mêmes flux OAuth
- régénérer ou vider les caches Laravel après un changement de domaine ou de protocole

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

