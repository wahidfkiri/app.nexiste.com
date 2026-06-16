<?php

namespace Vendor\Extensions\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Repositories\ExtensionRepository;
use Vendor\Extensions\Events\ExtensionActivated;
use Vendor\Extensions\Events\ExtensionDeactivated;
use Vendor\Extensions\Events\ExtensionSuspended;

class ExtensionService
{
    public function __construct(protected ExtensionRepository $repository) {}

    public function ensureCatalogSeeded(): void
    {
        if (!Schema::hasTable('extensions')) {
            return;
        }

        $defaults = [
            [
                'slug' => 'clients',
                'name' => 'Clients CRM',
                'tagline' => 'Gestion des clients et contacts',
                'description' => 'Module CRM client avec suivi des comptes, statuts et export.',
                'category' => 'productivity',
                'icon' => 'fa-users',
                'icon_bg_color' => '#2563eb',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_official' => true,
                'is_verified' => true,
                'sort_order' => 10,
            ],
            [
                'slug' => 'stock',
                'name' => 'Stock',
                'tagline' => 'Articles, fournisseurs et commandes',
                'description' => 'Pilotage de stock multi-tenant avec alertes et mouvements.',
                'category' => 'productivity',
                'icon' => 'fa-boxes-stacked',
                'icon_bg_color' => '#0891b2',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_official' => true,
                'is_verified' => true,
                'sort_order' => 20,
            ],
            [
                'slug' => 'invoice',
                'name' => 'Facturation',
                'tagline' => 'Devis, factures, paiements et rapports',
                'description' => 'Module de facturation avec cycle complet devis-facture-paiement.',
                'category' => 'finance',
                'icon' => 'fa-file-invoice',
                'icon_bg_color' => '#7c3aed',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_official' => true,
                'is_verified' => true,
                'sort_order' => 30,
            ],
            [
                'slug' => 'projects',
                'name' => 'Gestion Projets',
                'tagline' => 'Pilotage projets et taches type Asana',
                'description' => 'Gestion complete des projets, Kanban, membres, taches, commentaires et suivi client.',
                'category' => 'productivity',
                'icon' => 'fa-diagram-project',
                'icon_bg_color' => '#0ea5e9',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_official' => true,
                'is_verified' => true,
                'sort_order' => 35,
            ],
            [
                'slug' => 'notion-workspace',
                'name' => 'Notion Workspace',
                'tagline' => 'Wiki equipe et documentation intelligente',
                'description' => 'Pages hierarchiques type Notion, partage, templates, favoris et lien client.',
                'category' => 'productivity',
                'icon' => 'fa-book-open',
                'icon_bg_color' => '#111827',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_official' => true,
                'is_verified' => true,
                'sort_order' => 36,
            ],
            [
                'slug' => 'trello-integration',
                'name' => 'Trello Integration',
                'tagline' => 'Boards Trello modernes dans le CRM',
                'description' => 'Synchronisez vos boards, listes et cartes Trello dans une interface SaaS dediee, sans toucher au module Projet interne.',
                'category' => 'productivity',
                'icon' => 'fab fa-trello',
                'icon_bg_color' => '#026aa7',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_official' => true,
                'is_verified' => true,
                'sort_order' => 37,
            ],
            [
                'slug' => 'google-drive',
                'name' => 'Google Drive',
                'tagline' => 'Stockez, partagez et accedez a vos fichiers',
                'description' => 'Connectez Google Drive pour gerer vos fichiers depuis le CRM.',
                'category' => 'storage',
                'icon' => 'fa-google-drive',
                'icon_bg_color' => '#4285F4',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 40,
            ],
            [
                'slug' => 'dropbox',
                'name' => 'Dropbox',
                'tagline' => 'Stockage cloud et partage de documents',
                'description' => 'Connectez Dropbox pour gerer vos dossiers, fichiers et liens de partage depuis le CRM.',
                'category' => 'storage',
                'icon' => 'fa-dropbox',
                'icon_bg_color' => '#0061FF',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 45,
            ],
            [
                'slug' => 'google-calendar',
                'name' => 'Google Calendar',
                'tagline' => 'Synchronisez vos rendez-vous',
                'description' => 'Connexion Google Calendar avec synchronisation des evenements.',
                'category' => 'productivity',
                'icon' => 'fa-calendar-days',
                'icon_bg_color' => '#4285F4',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 50,
            ],
            [
                'slug' => 'google-sheets',
                'name' => 'Google Sheets',
                'tagline' => 'Lisez et mettez a jour vos feuilles',
                'description' => 'Creation, lecture, edition et suppression de Google Sheets.',
                'category' => 'productivity',
                'icon' => 'fa-file-excel',
                'icon_bg_color' => '#0f9d58',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 60,
            ],
            [
                'slug' => 'google-docx',
                'name' => 'Google Docs',
                'tagline' => 'Documents Google depuis le CRM',
                'description' => 'Creation et edition de documents Google Docs.',
                'category' => 'productivity',
                'icon' => 'fa-file-word',
                'icon_bg_color' => '#1a73e8',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 70,
            ],
            [
                'slug' => 'google-gmail',
                'name' => 'Google Gmail',
                'tagline' => 'Messagerie Gmail complete dans le CRM',
                'description' => 'Connexion Gmail OAuth, lecture, envoi, reponse, transfert, archivage et gestion des emails.',
                'category' => 'communication',
                'icon' => 'fa-envelope-open-text',
                'icon_bg_color' => '#ea4335',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 80,
            ],
            [
                'slug' => 'google-meet',
                'name' => 'Google Meet',
                'tagline' => 'Planifiez et gerez vos reunions video',
                'description' => 'Connexion Google Meet OAuth, creation de liens Meet, synchronisation et gestion complete des reunions.',
                'category' => 'communication',
                'icon' => 'fa-video',
                'icon_bg_color' => '#34a853',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 85,
            ],
            [
                'slug' => 'slack',
                'name' => 'Slack',
                'tagline' => 'Messagerie equipe et collaboration en temps reel',
                'description' => 'Connexion OAuth Slack, synchronisation des canaux/messages et envoi de messages avec Socket.IO.',
                'category' => 'communication',
                'icon' => 'fa-slack',
                'icon_bg_color' => '#4A154B',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 90,
            ],
            [
                'slug' => 'chatbot',
                'name' => 'Chatbot',
                'tagline' => 'Messagerie equipe en temps reel style Slack',
                'description' => 'Chat temps reel avec salons, rooms, emojis et partage de fichiers via Socket.IO.',
                'category' => 'communication',
                'icon' => 'fa-comments',
                'icon_bg_color' => '#0ea5e9',
                'pricing_type' => 'free',
                'status' => 'active',
                'is_featured' => true,
                'is_verified' => true,
                'sort_order' => 95,
            ],
        ];

        foreach ($defaults as $entry) {
            Extension::query()->firstOrCreate(
                ['slug' => $entry['slug']],
                $entry
            );
        }
    }

    // ── Catalogue CRUD (super-admin) ────────────────────────────────────────

    public function createExtension(array $data): Extension
    {
        return DB::transaction(function () use ($data) {
            // Générer slug si absent
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            // Assurer unicité du slug
            $base  = $data['slug'];
            $count = 1;
            while (Extension::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $base . '-' . $count++;
            }

            $extension = $this->repository->create($data);

            Log::channel('daily')->info("[Extension] Créée : {$extension->slug}");

            return $extension;
        });
    }

    public function updateExtension(Extension $extension, array $data): Extension
    {
        return DB::transaction(function () use ($extension, $data) {
            // Upload icône
            if (!empty($data['icon_file'])) {
                if ($extension->icon && !str_starts_with($extension->icon, 'fa-')) {
                    Storage::disk('public')->delete($extension->icon);
                }
                $data['icon'] = $data['icon_file']->store(
                    config('extensions.upload.icon_path', 'extensions/icons'),
                    config('extensions.upload.disk', 'public')
                );
                unset($data['icon_file']);
            }

            // Upload banner
            if (!empty($data['banner_file'])) {
                if ($extension->banner) {
                    Storage::disk('public')->delete($extension->banner);
                }
                $data['banner'] = $data['banner_file']->store(
                    config('extensions.upload.banner_path', 'extensions/banners'),
                    config('extensions.upload.disk', 'public')
                );
                unset($data['banner_file']);
            }

            return $this->repository->update($extension, $data);
        });
    }

    public function deleteExtension(Extension $extension): bool
    {
        if ($extension->active_installs_count > 0) {
            throw new \RuntimeException(
                "Impossible de supprimer une extension avec {$extension->active_installs_count} installation(s) active(s)."
            );
        }
        return DB::transaction(fn() => $this->repository->delete($extension));
    }

    public function toggleFeatured(Extension $extension): Extension
    {
        return $this->repository->update($extension, ['is_featured' => !$extension->is_featured]);
    }

    public function toggleStatus(Extension $extension): Extension
    {
        $newStatus = $extension->status === 'active' ? 'inactive' : 'active';
        return $this->repository->update($extension, ['status' => $newStatus]);
    }

    public function getStats(): array
    {
        return $this->repository->getStats();
    }

    // ── Marketplace / Tenant ────────────────────────────────────────────────

    public function getMarketplace(array $filters, int $tenantId)
    {
        $perPage = min((int)($filters['per_page'] ?? 20), 100);
        return $this->repository->getMarketplace($filters, $tenantId, $perPage);
    }

    public function getTenantExtensions(int $tenantId, array $filters = [])
    {
        return $this->repository->getTenantExtensions($tenantId, $filters);
    }

    // ── Activation ──────────────────────────────────────────────────────────

    public function activate(Extension $extension, int $tenantId, int $userId, array $options = []): TenantExtension
    {
        return DB::transaction(function () use ($extension, $tenantId, $userId, $options) {
            if ($extension->status !== 'active') {
                throw new \RuntimeException('Cette extension n\'est pas disponible.');
            }

            $existing = $this->repository->getTenantActivation($tenantId, $extension->id);

            if ($existing && in_array($existing->status, ['active', 'trial'])) {
                throw new \RuntimeException('Cette extension est déjà activée.');
            }

            // Déterminer le mode (trial ou direct)
            $isTrial  = $extension->has_trial && !$existing;
            $status   = $isTrial ? 'trial' : 'active';

            $activationData = [
                'tenant_id'     => $tenantId,
                'extension_id'  => $extension->id,
                'activated_by'  => $userId,
                'status'        => $status,
                'activated_at'  => now(),
                'billing_cycle' => $options['billing_cycle'] ?? $extension->billing_cycle,
                'price_paid'    => $options['price_paid']    ?? $extension->price,
                'currency'      => $extension->currency,
            ];

            if ($isTrial) {
                $activationData['trial_ends_at'] = now()->addDays($extension->trial_days);
            }

            if ($existing) {
                $activation = $this->repository->updateActivation($existing, $activationData);
            } else {
                $activation = $this->repository->createActivation($activationData);
            }

            // Mettre à jour les compteurs
            $extension->incrementInstalls();

            // Log
            $this->repository->logActivity([
                'extension_id' => $extension->id,
                'tenant_id'    => $tenantId,
                'user_id'      => $userId,
                'event'        => $isTrial ? 'trial_started' : 'activated',
                'payload'      => ['billing_cycle' => $activationData['billing_cycle']],
            ]);

            event(new ExtensionActivated($activation));

            Log::channel('daily')->info("[Extension] Activée : {$extension->slug} pour tenant #{$tenantId}");

            return $activation->fresh(['extension']);
        });
    }

    public function deactivate(TenantExtension $activation, int $userId, string $reason = ''): TenantExtension
    {
        return DB::transaction(function () use ($activation, $userId, $reason) {
            $result = $this->repository->updateActivation($activation, [
                'status'         => 'inactive',
                'deactivated_at' => now(),
            ]);

            $activation->extension->decrementActiveInstalls();

            $this->repository->logActivity([
                'extension_id' => $activation->extension_id,
                'tenant_id'    => $activation->tenant_id,
                'user_id'      => $userId,
                'event'        => 'deactivated',
                'payload'      => ['reason' => $reason],
            ]);

            event(new ExtensionDeactivated($activation));

            return $result;
        });
    }

    public function suspend(TenantExtension $activation, string $reason, string $suspendedBy): TenantExtension
    {
        return DB::transaction(function () use ($activation, $reason, $suspendedBy) {
            $result = $this->repository->updateActivation($activation, [
                'status'           => 'suspended',
                'suspended_at'     => now(),
                'suspension_reason'=> $reason,
                'suspended_by'     => $suspendedBy,
            ]);

            $this->repository->logActivity([
                'extension_id' => $activation->extension_id,
                'tenant_id'    => $activation->tenant_id,
                'user_id'      => null,
                'event'        => 'suspended',
                'payload'      => ['reason' => $reason],
            ]);

            event(new ExtensionSuspended($activation));

            return $result;
        });
    }

    public function saveSettings(TenantExtension $activation, array $settings): TenantExtension
    {
        $activation = $this->repository->updateActivation($activation, ['settings' => $settings]);

        $this->repository->logActivity([
            'extension_id' => $activation->extension_id,
            'tenant_id'    => $activation->tenant_id,
            'user_id'      => auth()->id(),
            'event'        => 'configured',
        ]);

        return $activation;
    }

    // ── SuperAdmin activations overview ────────────────────────────────────

    public function getAllActivations(array $filters)
    {
        $perPage = min((int)($filters['per_page'] ?? 20), 100);
        return $this->repository->getAllActivationsPaginated($filters, $perPage);
    }
}
