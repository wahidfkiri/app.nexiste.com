<?php

namespace Vendor\Automation\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use NexusExtensions\Chatbot\Models\ChatbotRoom;
use NexusExtensions\Chatbot\Services\ChatbotService;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Slack\Models\SlackChannel;
use NexusExtensions\Slack\Models\SlackToken;
use NexusExtensions\Slack\Services\SlackSocketService;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;

class CreateProjectChannelAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected ChatbotService $chatbotService,
        protected SlackSocketService $slackSocketService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $payload = $this->payload($automationEvent);
        $projectId = $this->modelId($payload, $suggestion, 'project_id', Project::class);
        if (!$projectId) {
            throw new RuntimeException('Projet introuvable pour la création du canal équipe.');
        }

        $project = $this->loadProject($tenantId, $projectId);
        $preferredProvider = (string) ($payload['extension_slug'] ?? '');
        $provider = $this->resolveProvider($tenantId, $preferredProvider);

        return $provider === 'slack'
            ? $this->withReconnectHandling('slack', fn () => $this->createSlackChannel($automationEvent, $project))
            : $this->createChatbotRoom($automationEvent, $project);
    }

    protected function resolveProvider(int $tenantId, string $preferredProvider): string
    {
        $preferredProvider = trim($preferredProvider);
        if ($preferredProvider !== '' && $this->extensions->isActive($tenantId, $preferredProvider)) {
            return $preferredProvider;
        }

        if ($this->extensions->isActive($tenantId, 'chatbot')) {
            return 'chatbot';
        }

        if ($this->extensions->isActive($tenantId, 'slack')) {
            return 'slack';
        }

        throw new RuntimeException('Aucun canal de communication disponible pour ce tenant.');
    }

    protected function createChatbotRoom(AutomationEvent $automationEvent, Project $project): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'chatbot', 'Chatbot doit être installé pour créer un canal équipe.');

        $existingMeta = $this->projectMetadata($project, 'chatbot_room', []);
        $existingRoomId = (int) ($existingMeta['id'] ?? 0);
        if ($existingRoomId > 0) {
            $room = ChatbotRoom::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($existingRoomId)
                ->first();

            if ($room) {
                return [
                    'result' => 'channel_exists',
                    'message' => 'Le salon Chatbot du projet existe déjà.',
                    'provider' => 'chatbot',
                    'project_id' => (int) $project->id,
                    'room_id' => (int) $room->id,
                    'target_url' => $this->routeUrl('chatbot.index'),
                ];
            }
        }

        $actor = $this->resolveActorUser($automationEvent);
        $roomName = $this->chatbotRoomName($project);
        $room = ChatbotRoom::query()
            ->where('tenant_id', $tenantId)
            ->where('name', $roomName)
            ->first();

        if (!$room) {
            $memberIds = $project->members
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->push((int) $project->owner_id)
                ->unique()
                ->values()
                ->all();

            $room = $this->chatbotService->createRoom($tenantId, $actor, [
                'name' => $roomName,
                'description' => 'Canal equipe du projet ' . $project->name,
                'icon' => 'fa-diagram-project',
                'color' => (string) ($project->color ?: '#2563eb'),
                'is_private' => true,
                'member_ids' => $memberIds,
            ]);
        }

        $this->updateProjectMetadata($project, 'chatbot_room', [
            'id' => (int) $room->id,
            'room_uuid' => (string) $room->room_uuid,
            'name' => (string) $room->name,
            'created_at' => now()->toIso8601String(),
        ]);

        $this->logProjectActivity(
            $tenantId,
            $project,
            null,
            'project_channel_created',
            'Salon Chatbot créé pour le projet',
            ['provider' => 'chatbot', 'room_id' => (int) $room->id],
            $this->actorId($automationEvent)
        );

        return [
            'result' => 'channel_created',
            'message' => 'Salon Chatbot créé pour le projet.',
            'provider' => 'chatbot',
            'project_id' => (int) $project->id,
            'room_id' => (int) $room->id,
            'target_url' => $this->routeUrl('chatbot.index'),
        ];
    }

    protected function createSlackChannel(AutomationEvent $automationEvent, Project $project): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'slack', 'Slack doit être installé pour créer un canal équipe.');

        $existingMeta = $this->projectMetadata($project, 'slack_channel', []);
        $existingChannelId = trim((string) ($existingMeta['channel_id'] ?? ''));
        if ($existingChannelId !== '') {
            $channel = SlackChannel::query()
                ->where('tenant_id', $tenantId)
                ->where('channel_id', $existingChannelId)
                ->first();

            if ($channel) {
                return [
                    'result' => 'channel_exists',
                    'message' => 'Le canal Slack du projet existe déjà.',
                    'provider' => 'slack',
                    'project_id' => (int) $project->id,
                    'channel_id' => (string) $channel->channel_id,
                    'target_url' => $this->routeUrl('slack.index'),
                ];
            }
        }

        $token = SlackToken::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$token || trim((string) $token->bot_token) === '') {
            throw new RuntimeException("Slack n'est pas connecté pour ce tenant.");
        }

        $channelName = $this->slackChannelName($project);
        $baseUrl = rtrim((string) config('slack.api.base_url', 'https://slack.com/api'), '/');
        $response = Http::withToken((string) $token->bot_token)
            ->acceptJson()
            ->timeout((int) config('slack.api.timeout', 20))
            ->asJson()
            ->post($baseUrl . '/conversations.create', [
                'name' => $channelName,
                'is_private' => false,
            ]);

        if (!$response->ok()) {
            throw new RuntimeException('Erreur API Slack conversations.create : HTTP ' . $response->status());
        }

        $data = $response->json();
        if (!is_array($data) || !($data['ok'] ?? false)) {
            $error = trim((string) ($data['error'] ?? ''));
            throw new RuntimeException($error !== '' ? 'Erreur API Slack : ' . $error : 'La création du canal Slack a échoué.');
        }

        $channelData = (array) ($data['channel'] ?? []);
        $channelId = trim((string) ($channelData['id'] ?? ''));
        if ($channelId === '') {
            throw new RuntimeException('Slack n a pas retourne d identifiant de canal.');
        }

        SlackChannel::query()
            ->where('tenant_id', $tenantId)
            ->update(['is_selected' => false]);

        $channel = SlackChannel::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'channel_id' => $channelId],
            [
                'name' => (string) ($channelData['name'] ?? $channelName),
                'is_private' => (bool) ($channelData['is_private'] ?? false),
                'is_im' => false,
                'is_mpim' => false,
                'is_archived' => false,
                'is_member' => true,
                'is_selected' => true,
                'num_members' => isset($channelData['num_members']) ? (int) $channelData['num_members'] : null,
                'topic' => (string) (($channelData['topic']['value'] ?? '') ?: ''),
                'purpose' => (string) (($channelData['purpose']['value'] ?? '') ?: ''),
                'synced_at' => now(),
                'raw' => $channelData,
            ]
        );

        $token->update([
            'selected_channel_id' => $channelId,
            'selected_channel_name' => (string) $channel->name,
        ]);

        $this->slackSocketService->emit($tenantId, 'channel.created', [
            'tenant_id' => $tenantId,
            'channel' => [
                'channel_id' => $channelId,
                'name' => (string) $channel->name,
            ],
        ]);

        $this->updateProjectMetadata($project, 'slack_channel', [
            'channel_id' => $channelId,
            'name' => (string) $channel->name,
            'created_at' => now()->toIso8601String(),
        ]);

        $this->logProjectActivity(
            $tenantId,
            $project,
            null,
            'project_channel_created',
            'Canal Slack créé pour le projet',
            ['provider' => 'slack', 'channel_id' => $channelId],
            $this->actorId($automationEvent)
        );

        return [
            'result' => 'channel_created',
            'message' => 'Canal Slack créé pour le projet.',
            'provider' => 'slack',
            'project_id' => (int) $project->id,
            'channel_id' => $channelId,
            'target_url' => $this->routeUrl('slack.index'),
        ];
    }

    protected function chatbotRoomName(Project $project): string
    {
        return Str::limit('Projet ' . (int) $project->id . ' - ' . $project->name, 120, '');
    }

    protected function slackChannelName(Project $project): string
    {
        $slug = Str::slug('projet-' . (int) $project->id . '-' . $project->name, '-');
        $slug = trim($slug, '-');
        $slug = $slug !== '' ? $slug : 'projet-' . (int) $project->id;

        return Str::limit($slug, 80, '');
    }
}
