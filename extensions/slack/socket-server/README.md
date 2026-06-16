# Slack Socket.IO Server

Serveur Socket.IO minimal pour la diffusion temps reel de l extension Slack.

## Installation

```bash
cd extensions/slack/socket-server
npm install
```

## Lancement

```bash
npm start
```

Le serveur ecoute par defaut sur `http://127.0.0.1:6002`.

## Variables utiles

- `PORT` (defaut: `6002`)
- `CORS_ORIGIN` (defaut: `*`)
- `SOCKET_IO_PATH` (defaut: `/socket.io`)
- `SLACK_SOCKET_IO_SERVER_TOKEN` (optionnel, recommande)

## Endpoint backend

`POST /emit`

Payload:

```json
{
  "tenant_id": 1,
  "event": "message.created",
  "payload": { "channel_id": "C123", "message": { "text": "hello" } }
}
```

Si `SLACK_SOCKET_IO_SERVER_TOKEN` est defini, envoyer `Authorization: Bearer <token>`.

