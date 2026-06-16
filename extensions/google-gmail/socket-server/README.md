# Google Gmail Socket.IO Server

Serveur Socket.IO minimal pour diffuser les mises a jour Gmail en temps reel dans Nexus CRM.

## Installation

```bash
cd extensions/google-gmail/socket-server
npm install
```

## Lancement

```bash
npm start
```

Le serveur ecoute par defaut sur `http://127.0.0.1:6004`.

## Variables utiles

- `PORT` (defaut: `6004`)
- `CORS_ORIGIN` (defaut: `*`)
- `SOCKET_IO_PATH` (defaut: `/socket.io`)
- `GOOGLE_GMAIL_SOCKET_IO_SERVER_TOKEN` (optionnel, recommande)

## Endpoint backend

`POST /emit`

Payload:

```json
{
  "tenant_id": 1,
  "event": "mailbox.synced",
  "payload": {
    "stats": { "unread_total": 3 }
  }
}
```

Si `GOOGLE_GMAIL_SOCKET_IO_SERVER_TOKEN` est defini, envoyer `Authorization: Bearer <token>`.
