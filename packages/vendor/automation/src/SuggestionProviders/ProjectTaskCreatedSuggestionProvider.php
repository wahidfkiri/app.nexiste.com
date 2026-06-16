<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class ProjectTaskCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $task = (array) ($context['task'] ?? []);
        $project = (array) ($context['project'] ?? []);
        $meta = (array) ($context['meta'] ?? []);
        $taskId = (int) ($task['id'] ?? 0);
        $projectId = (int) ($project['id'] ?? 0);

        if ($tenantId <= 0 || $taskId <= 0 || $projectId <= 0) {
            return [];
        }

        $taskTitle = (string) ($task['title'] ?? 'cette tâche');
        $calendarSynced = (bool) ($task['calendar_synced'] ?? false) || (bool) ($meta['calendar_synced'] ?? false);
        $hasStorageFolder = (bool) ($project['has_drive_folder'] ?? false);
        $hasTeamChannel = (bool) ($project['has_team_channel'] ?? false);

        $suggestions = [];

        if (!$calendarSynced) {
            $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
            $suggestions[] = SuggestionDefinition::make(
                $calendarInstalled ? 'schedule_project_task_calendar' : 'install_extension',
                $calendarInstalled
                    ? 'Planifier la tâche ' . $taskTitle . ' dans Google Calendar'
                    : 'Installer Google Calendar pour planifier les tâches du projet',
                0.92,
                $calendarInstalled
                    ? ['project_id' => $projectId, 'task_id' => $taskId]
                    : ['extension_slug' => 'google-calendar', 'project_id' => $projectId, 'task_id' => $taskId, 'target_action' => 'schedule_project_task_calendar'],
                [
                    'integration' => 'google-calendar',
                    'installed' => $calendarInstalled,
                    'target_url' => $this->extensions->targetUrl('google-calendar'),
                ]
            );
        }

        $notionInstalled = $this->extensions->isActive($tenantId, 'notion-workspace');
        $suggestions[] = SuggestionDefinition::make(
            $notionInstalled ? 'create_notion_page' : 'install_extension',
            $notionInstalled
                ? 'Créer une page Notion de spécification pour la tâche ' . $taskTitle
                : 'Installer Notion Workspace pour documenter les tâches projet',
            0.81,
            $notionInstalled
                ? [
                    'project_id' => $projectId,
                    'task_id' => $taskId,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'task_spec',
                    'context_label' => 'Spécification de tâche',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'project_id' => $projectId,
                    'task_id' => $taskId,
                    'target_action' => 'create_notion_page',
                    'template' => 'task_spec',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'task_spec',
            ]
        );

        if (!$hasStorageFolder) {
            $preferredStorage = $this->extensions->preferredInstalled($tenantId, ['google-drive', 'dropbox']);
            $storageInstalled = $preferredStorage !== null;
            $storageSlug = $preferredStorage ?: 'dropbox';
            $storageAction = $storageSlug === 'dropbox' ? 'create_project_dropbox_folder' : 'create_project_drive_folder';

            $suggestions[] = SuggestionDefinition::make(
                $storageInstalled ? $storageAction : 'install_extension',
                $storageInstalled
                    ? ($storageSlug === 'dropbox'
                        ? 'Créer le dossier Dropbox du projet pour cette tâche'
                        : 'Créer le dossier Google Drive du projet pour cette tâche')
                    : 'Installer Dropbox ou Google Drive pour centraliser les fichiers du projet',
                0.82,
                $storageInstalled
                    ? ['project_id' => $projectId, 'task_id' => $taskId]
                    : ['extension_slug' => 'dropbox', 'project_id' => $projectId, 'task_id' => $taskId, 'target_action' => 'create_project_dropbox_folder'],
                [
                    'integration' => $storageSlug,
                    'installed' => $storageInstalled,
                    'target_url' => $this->extensions->targetUrl($storageSlug),
                ]
            );
        }

        if (!$hasTeamChannel) {
            $installedChannel = $this->extensions->preferredInstalled($tenantId, ['chatbot', 'slack']);
            $channelInstalled = $installedChannel !== null;
            $channelSlug = $installedChannel ?: 'chatbot';

            $suggestions[] = SuggestionDefinition::make(
                $channelInstalled ? 'create_project_channel' : 'install_extension',
                $channelInstalled
                    ? "Créer un canal d'équipe pour coordonner cette tâche"
                    : 'Installer Chatbot ou Slack pour discuter autour des tâches du projet',
                0.73,
                $channelInstalled
                    ? ['project_id' => $projectId, 'task_id' => $taskId, 'extension_slug' => $channelSlug]
                    : ['extension_slug' => 'chatbot', 'project_id' => $projectId, 'task_id' => $taskId, 'target_action' => 'create_project_channel'],
                [
                    'integration' => $channelSlug,
                    'installed' => $channelInstalled,
                    'target_url' => $this->extensions->targetUrl($channelSlug),
                ]
            );
        }

        return $suggestions;
    }
}
