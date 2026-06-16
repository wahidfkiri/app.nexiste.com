<?php

namespace Vendor\Automation\Actions;

use NexusExtensions\GoogleDrive\Models\GoogleDriveFile;
use NexusExtensions\GoogleDrive\Services\GoogleDriveService;
use NexusExtensions\Projects\Models\Project;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;

class CreateProjectDriveFolderAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected GoogleDriveService $driveService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return $this->withReconnectHandling('google-drive', function () use ($automationEvent, $suggestion) {
            $tenantId = $this->tenantId($automationEvent);
            $this->assertExtensionActive($tenantId, 'google-drive', 'Google Drive doit être installé pour créer un dossier projet.');

            if (!$this->driveService->getToken($tenantId)) {
                throw new RuntimeException("Google Drive n'est pas connecté pour ce tenant.");
            }

            $payload = $this->payload($automationEvent);
            $projectId = $this->modelId($payload, $suggestion, 'project_id', Project::class);
            if (!$projectId) {
                throw new RuntimeException('Projet introuvable pour la création du dossier Drive.');
            }

            $project = $this->loadProject($tenantId, $projectId);
            $existingFolderId = trim((string) $this->projectMetadata($project, 'drive_folder_id', ''));
            if ($existingFolderId !== '') {
                try {
                    $existingFolder = $this->driveService->getFile($tenantId, $existingFolderId);

                    return [
                        'result' => 'drive_folder_exists',
                        'message' => 'Le dossier Google Drive du projet existe déjà.',
                        'project_id' => (int) $project->id,
                        'folder_id' => $existingFolderId,
                        'target_url' => $existingFolder['web_view_link'] ?? $this->routeUrl('google-drive.index'),
                    ];
                } catch (\Throwable) {
                    // Recreate below.
                }
            }

            $token = $this->driveService->getToken($tenantId);
            $rootId = $token?->drive_root_folder_id ?: null;

            $projectsFolder = null;
            if ($rootId) {
                $projectsFolder = GoogleDriveFile::forTenant($tenantId)
                    ->where('is_folder', true)
                    ->where('name', 'Projets')
                    ->where('parent_drive_id', $rootId)
                    ->first();
            }

            $projectsFolderId = (string) ($projectsFolder?->drive_id ?? '');
            if ($projectsFolderId === '') {
                $createdRootFolder = $this->driveService->createFolder($tenantId, 'Projets', $rootId);
                $projectsFolderId = (string) ($createdRootFolder['id'] ?? '');
            }

            if ($projectsFolderId === '') {
                throw new RuntimeException('Impossible de créer le dossier racine Projets dans Google Drive.');
            }

            $projectFolderName = 'Projet-' . (int) $project->id;
            $projectFolder = GoogleDriveFile::forTenant($tenantId)
                ->where('is_folder', true)
                ->where('name', $projectFolderName)
                ->where('parent_drive_id', $projectsFolderId)
                ->first();

            $folderData = null;
            $projectFolderId = (string) ($projectFolder?->drive_id ?? '');
            if ($projectFolderId !== '') {
                $folderData = $this->driveService->getFile($tenantId, $projectFolderId);
            } else {
                $folderData = $this->driveService->createFolder($tenantId, $projectFolderName, $projectsFolderId);
                $projectFolderId = (string) ($folderData['id'] ?? '');
            }

            if ($projectFolderId === '') {
                throw new RuntimeException('Impossible de créer le dossier Google Drive du projet.');
            }

            $this->updateProjectMetadata($project, 'drive_folder_id', $projectFolderId);
            $this->updateProjectMetadata($project, 'drive_folder', [
                'id' => $projectFolderId,
                'name' => (string) ($folderData['name'] ?? $projectFolderName),
                'web_view_link' => (string) ($folderData['web_view_link'] ?? ''),
                'created_at' => now()->toIso8601String(),
            ]);

            $this->logProjectActivity(
                $tenantId,
                $project,
                null,
                'project_drive_folder_created',
                'Dossier Google Drive créé pour le projet',
                ['folder_id' => $projectFolderId],
                $this->actorId($automationEvent)
            );

            return [
                'result' => 'drive_folder_created',
                'message' => 'Dossier Google Drive créé pour le projet.',
                'project_id' => (int) $project->id,
                'folder_id' => $projectFolderId,
                'target_url' => $folderData['web_view_link'] ?? $this->routeUrl('google-drive.index'),
            ];
        });
    }
}
