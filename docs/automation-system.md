# Système d'automation intelligent du CRM

## Objectif

Le CRM embarque un moteur d'automation `human-in-the-loop` :

- une action métier réussie déclenche un événement
- le moteur génère des suggestions contextuelles
- l'utilisateur accepte ou ignore
- seule une suggestion acceptée déclenche une action réelle

Rien d'important n'est exécuté automatiquement sans validation utilisateur.

## Architecture générale

```text
Action métier
  -> Event Laravel métier
  -> CaptureAutomationSuggestions
  -> AutomationEngine
  -> SuggestionProviders
  -> automation_suggestions
  -> validation utilisateur
  -> automation_events
  -> AutomationExecutor / Job
  -> intégration ou module cible
```

## Sources métier actuellement branchées

- `client_created`
- `invoice_created`
- `quote_created`
- `project_created`
- `project_task_created`
- `user_invited`
- `extension_activated`

## Actions actuellement supportées

- `send_welcome_email`
- `create_followup_meeting`
- `create_quote`
- `send_invoice_email`
- `schedule_invoice_reminder`
- `create_payment_followup_task`
- `send_quote_email`
- `schedule_quote_followup`
- `create_quote_followup_task`
- `schedule_project_kickoff`
- `schedule_project_task_calendar`
- `send_team_invitation_followup_email`
- `schedule_user_onboarding_meeting`
- `create_user_onboarding_task`
- `create_project_drive_folder`
- `create_project_dropbox_folder`
- `create_project_channel`
- `create_notion_page`
- `open_extension_workspace`
- `install_extension`

Configuration réelle :

- `packages/vendor/automation/config/automation.php`

## Intégrations réellement utilisées par le moteur

### Exécution directe

- `Google Gmail`
- `Google Calendar`
- `Google Drive`
- `Dropbox`
- `Slack`
- `Notion Workspace`
- `Projects`
- `Invoice`

### Ouverture de workspace / guidage utilisateur

- `Google Gmail`
- `Google Calendar`
- `Google Drive`
- `Google Meet`
- `Google Sheets`
- `Google Docs`
- `Notion Workspace`
- `Slack`
- `Chatbot`
- `Marketplace`

## Notion Workspace dans automation

Le module Notion est maintenant branché sur deux scénarios.

### 1. Ouvrir Notion Workspace

Une suggestion peut ouvrir directement l'extension Notion depuis le CRM.

Cas typiques :

- après activation d'extension
- depuis certaines suggestions métier

Comportement notable :

- pour Notion, l'ouverture peut être marquée `target_blank`
- le front respecte ce flag et ouvre la cible dans un nouvel onglet

### 2. Créer une page Notion réelle

Le moteur peut créer une page dans le vrai workspace Notion connecté :

- création via l'API officielle Notion
- lien CRM enregistré dans `notion_page_links`
- page reliée à un `client` et/ou un `project`

Cas déjà câblés :

- création d'une page de notes client
- création d'un brief projet
- création de notes ou page de suivi sur certains flux `quote` / `invoice` / `task`

## Gestion des authentifications expirées

Le moteur gère maintenant le cas où une suggestion acceptée dépend d'une intégration dont la session est expirée.

Providers reconnus aujourd'hui :

- `google-gmail`
- `google-calendar`
- `google-drive`
- `dropbox`
- `slack`
- `google-meet`
- `google-sheets`
- `google-docx`
- `notion-workspace`

Logique actuelle :

1. l'action échoue avec un message de reconnexion clair
2. l'événement est marqué `failed`
3. la suggestion repasse en `pending`
4. une notification de reprise peut être créée
5. après reconnexion réussie, l'utilisateur retrouve la suggestion en attente

Composants clés :

- `packages/vendor/automation/src/Support/AutomationReconnectResolver.php`
- `packages/vendor/automation/src/Services/AutomationReconnectNotificationService.php`
- `app/Notifications/AutomationSuggestionPendingNotification.php`

## Scénario de reprise après reconnexion

Flux utilisateur actuel :

1. l'utilisateur accepte une suggestion
2. l'intégration répond `session expirée` ou `reconnectez ...`
3. la suggestion reste disponible
4. l'utilisateur reconnecte l'extension concernée
5. une notification apparaît dans le header
6. au clic, la notification est marquée comme lue
7. le modal des suggestions se rouvre avec la suggestion encore en attente

Ce comportement est prévu pour tous les providers reconnus par `AutomationReconnectResolver`.

## Drafts et automation

Le système de brouillons est volontairement séparé du moteur d'automation :

- un draft sert uniquement à reprendre une saisie inachevée
- il est supprimé après succès métier
- il ne génère pas de notification immédiate de confort
- seuls les rappels différés passent par le scheduler `drafts:remind`

## Tables principales

### `automation_suggestions`

Stocke les suggestions visibles côté utilisateur.

Champs importants :

- `tenant_id`
- `user_id`
- `source_event`
- `source_type`
- `source_id`
- `type`
- `label`
- `payload`
- `meta`
- `status`
- `dedupe_key`
- `expires_at`

### `automation_events`

Stocke l'exécution d'une suggestion acceptée.

Champs importants :

- `tenant_id`
- `user_id`
- `event_name`
- `action_type`
- `payload`
- `status`
- `idempotency_key`
- `triggered_by_suggestion_id`

### `automation_logs`

Stocke les journaux techniques d'exécution.

## Bonnes pratiques

- ne jamais appeler directement une intégration externe depuis un contrôleur métier pour de la logique automation
- toujours partir d'un événement métier
- toujours vérifier le `tenant_id`
- toujours vérifier si l'extension cible est active
- toujours gérer l'auth expirée de façon explicite
- garder les `SuggestionProvider` simples
- garder une action = une responsabilité

## Fichiers clés

### Noyau

- `packages/vendor/automation/config/automation.php`
- `packages/vendor/automation/src/Services/AutomationEngine.php`
- `packages/vendor/automation/src/Services/AutomationExecutor.php`
- `packages/vendor/automation/src/Listeners/CaptureAutomationSuggestions.php`
- `packages/vendor/automation/src/Jobs/ExecuteAutomationEventJob.php`

### Reconnexion / reprise

- `packages/vendor/automation/src/Support/AutomationReconnectResolver.php`
- `packages/vendor/automation/src/Services/AutomationReconnectNotificationService.php`
- `app/Notifications/AutomationSuggestionPendingNotification.php`

### Notion

- `packages/vendor/automation/src/Actions/CreateNotionPageAutomationAction.php`
- `extensions/notion-workspace/src/Services/NotionWorkspaceApiService.php`
- `extensions/notion-workspace/src/Models/NotionPageLink.php`

## Limites actuelles

Le moteur est opérationnel, mais il reste des axes possibles :

- historique visuel plus riche des automations
- retry manuel dédié pour les événements `failed`
- éditeur avancé de suggestion avant acceptation
- règles plus fines par équipe ou secteur
- scénarios combinés plus poussés entre Notion, Gmail, Calendar et Slack
