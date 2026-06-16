# Chatbot Socket.IO Server

Serveur Socket.IO minimal pour diffuser les evenements temps reel de l extension Chatbot.

## Installation

```bash
cd extensions/chatbot/socket-server
npm install
```

## Lancement

```bash
npm start
```

Le serveur ecoute par defaut sur `http://127.0.0.1:6003`.

## Variables utiles

- `PORT` (defaut: `6003`)
- `CORS_ORIGIN` (defaut: `*`)
- `SOCKET_IO_PATH` (defaut: `/socket.io`)
- `CHATBOT_SOCKET_IO_SERVER_TOKEN` (optionnel, recommande)

## Endpoint backend

`POST /emit`

Payload:

```json
{
  "tenant_id": 1,
  "event": "message.created",
  "payload": { "room_id": 12, "message": { "text": "hello" } }
}
```

Si `CHATBOT_SOCKET_IO_SERVER_TOKEN` est defini, envoyer `Authorization: Bearer <token>`.
