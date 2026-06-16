<?php

namespace Vendor\Automation\Actions;

use NexusExtensions\Dropbox\Models\DropboxFile;
use NexusExtensions\Dropbox\Services\DropboxService;
use NexusExtensions\Projects\Models\Project;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;

class CreateProjectDropboxFolderAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected DropboxService $dropboxService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return $this->withReconnectHandling('dropbox', function () use ($automationEvent, $suggestion) {
            $tenantId = $this->tenantId($automationEvent);
            $this->assertExtensionActive($tenantId, 'dropbox', 'Dropbox doit être installé pour créer un dossier projet.');

            if (!$this->dropboxService->getToken($tenantId)) {
                throw new RuntimeException("Dropbox n'est pas connecté pour ce tenant.");
            }

            $payload = $this->payload($automationEvent);
            $projectId = $this->modelId($payload, $suggestion, 'project_id', Project::class);
            if (!$projectId) {
                throw new RuntimeException('Projet introuvable pour la création du dossier Dropbox.');
            }

            $project = $this->loadProject($tenantId, $projectId);
            $existingFolderId = trim((string) $this->projectMetadata($project, 'dropbox_folder_id', ''));
            if ($existingFolderId !== '') {
                try {
                    $existingFolder = $this->dropboxService->getFile($tenantId, $existingFolderId);

                    return [
                        'result' => 'dropbox_folder_exists',
                        'message' => 'Le dossier Dropbox du projet existe déjà.',
                        'project_id' => (int) $project->id,
                        'folder_id' => $existingFolderId,
                        'target_url' => $existingFolder['web_view_link'] ?? $this->routeUrl('dropbox.index'),
                    ];
                } catch (\Throwable) {
                    // recreate below
                }
            }

            $token = $this->dropboxService->getToken($tenantId);
            $rootPath = (string) ($token?->dropbox_root_path ?? '');
            if ($rootPath === '') {
                throw new RuntimeException('Racine Dropbox introuvable pour ce tenant.');
            }

            $projectsFolder = DropboxFile::forTenant($tenantId)
                ->where('is_folder', true)
                ->where('name', 'Projets')
                ->where('parent_path_lower', $rootPath)
                ->first();

            $projectsFolderRef = (string) ($projectsFolder?->dropbox_id ?? '');
            if ($projectsFolderRef === '') {
                $createdRootFolder = $this->dropboxService->createFolder($tenantId, 'Projets', $rootPath);
                $projectsFolderRef = (string) ($createdRootFolder['id'] ?? '');
            }

            if ($projectsFolderRef === '') {
                throw new RuntimeException('Impossible de créer le dossier racine Projets dans Dropbox.');
            }

            $projectFolderName = 'Projet-' . (int) $project->id;
            $projectFolder = DropboxFile::forTenant($tenantId)
                ->where('is_folder', true)
                ->where('name', $projectFolderName)
                ->where('parent_path_lower', DropboxFile::forTenant($tenantId)->where('dropbox_id', $projectsFolderRef)->value('path_lower'))
                ->first();

            $folderData = null;
            $projectFolderId = (string) ($projectFolder?->dropbox_id ?? '');
            if ($projectFolderId !== '') {
                $folderData = $this->dropboxService->getFile($tenantId, $projectFolderId);
            } else {
                $folderData = $this->dropboxService->createFolder($tenantId, $projectFolderName, $projectsFolderRef);
                $projectFolderId = (string) ($folderData['id'] ?? '');
            }

            if ($projectFolderId === '') {
                throw new RuntimeException('Impossible de créer le dossier Dropbox du projet.');
            }

            $this->updateProjectMetadata($project, 'dropbox_folder_id', $projectFolderId);
            $this->updateProjectMetadata($project, 'dropbox_folder', [
                'id' => $projectFolderId,
                'name' => (string) ($folderData['name'] ?? $projectFolderName),
                'web_view_link' => (string) ($folderData['web_view_link'] ?? ''),
                'created_at' => now()->toIso8601String(),
            ]);

            $this->logProjectActivity(
                $tenantId,
                $project,
                null,
                'project_dropbox_folder_created',
                'Dossier Dropbox créé pour le projet',
                ['folder_id' => $projectFolderId],
                $this->actorId($automationEvent)
            );

            return [
                'result' => 'dropbox_folder_created',
                'message' => 'Dossier Dropbox créé pour le projet.',
                'project_id' => (int) $project->id,
                'folder_id' => $projectFolderId,
                'target_url' => $folderData['web_view_link'] ?? $this->routeUrl('dropbox.index'),
            ];
        });
    }
}
