<?php

namespace Vendor\Automation\Actions;

use App\Models\TenantUserMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectActivity;
use NexusExtensions\Projects\Models\ProjectMember;
use NexusExtensions\Projects\Models\ProjectTask;
use RuntimeException;
use Vendor\Automation\Contracts\AutomationAction;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Services\ExtensionAvailabilityService;
use Vendor\Automation\Support\AutomationReconnectResolver;
use Vendor\Client\Models\Client;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\Supplier;
use Vendor\User\Models\UserInvitation;
use Throwable;

abstract class AbstractAutomationAction implements AutomationAction
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    protected function payload(AutomationEvent $automationEvent): array
    {
        return is_array($automationEvent->payload) ? $automationEvent->payload : [];
    }

    protected function meta(?AutomationSuggestion $suggestion = null): array
    {
        return is_array($suggestion?->meta) ? $suggestion->meta : [];
    }

    protected function tenantId(AutomationEvent $automationEvent): int
    {
        $tenantId = (int) $automationEvent->tenant_id;
        if ($tenantId <= 0) {
            throw new RuntimeException(__('automation::automation.actions.tenant_missing'));
        }

        return $tenantId;
    }

    protected function actorId(AutomationEvent $automationEvent): ?int
    {
        return $automationEvent->user_id ? (int) $automationEvent->user_id : null;
    }

    protected function modelId(
        array $payload,
        ?AutomationSuggestion $suggestion,
        string $key,
        ?string $expectedSourceType = null
    ): ?int {
        $value = (int) ($payload[$key] ?? 0);
        if ($value > 0) {
            return $value;
        }

        if (
            $suggestion
            && $suggestion->source_id !== null
            && ($expectedSourceType === null || (string) $suggestion->source_type === $expectedSourceType)
        ) {
            $sourceId = (int) $suggestion->source_id;
            return $sourceId > 0 ? $sourceId : null;
        }

        return null;
    }

    protected function resolveActorUser(AutomationEvent $automationEvent): User
    {
        $tenantId = $this->tenantId($automationEvent);
        $preferredUserId = $this->actorId($automationEvent);

        if ($preferredUserId) {
            $user = User::query()->whereKey($preferredUserId)->first();
            if ($user && $user->hasTenantAccess($tenantId)) {
                return $user;
            }
        }

        $membership = TenantUserMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderByDesc('is_tenant_owner')
            ->orderByRaw("
                CASE role_in_tenant
                    WHEN 'owner' THEN 0
                    WHEN 'admin' THEN 1
                    WHEN 'manager' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('id')
            ->first();

        if ($membership?->user) {
            return $membership->user;
        }

        $user = User::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_tenant_owner')
            ->orderBy('id')
            ->first();

        if (!$user) {
            throw new RuntimeException(__('automation::automation.actions.no_actor'));
        }

        return $user;
    }

    protected function assertExtensionActive(int $tenantId, string $slug, ?string $message = null): void
    {
        if ($this->extensions->isActive($tenantId, $slug)) {
            return;
        }

        throw new RuntimeException($message ?: __('automation::automation.actions.extension_not_active', [
            'extension' => AutomationReconnectResolver::providerLabel($slug),
        ]));
    }

    protected function withReconnectHandling(string $providerSlug, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            $normalized = mb_strtolower(trim($e->getMessage()));

            if ($this->requiresReconnectForProvider($providerSlug, $normalized)) {
                $label = AutomationReconnectResolver::providerLabel($providerSlug);
                throw new RuntimeException(__('automation::automation.actions.provider_reconnect', [
                    'provider' => $label,
                ]));
            }

            throw $e;
        }
    }

    protected function requiresReconnectForProvider(string $providerSlug, string $message): bool
    {
        if ($message === '') {
            return false;
        }

        $providerLabel = mb_strtolower(AutomationReconnectResolver::providerLabel($providerSlug));

        return match ($providerSlug) {
            'google-gmail', 'google-calendar', 'google-drive', 'google-meet', 'google-sheets', 'google-docx' => str_contains($message, $providerLabel)
                || str_contains($message, 'reconnectez votre compte google')
                || str_contains($message, 'session google')
                || str_contains($message, 'refresh token is missing')
                || str_contains($message, 'invalid_grant'),
            'dropbox' => str_contains($message, 'dropbox')
                && (
                    str_contains($message, 'reconnexion')
                    || str_contains($message, 'reconnect')
                    || str_contains($message, 'refresh token')
                    || str_contains($message, 'invalid_grant')
                    || str_contains($message, 'invalid_access_token')
                    || str_contains($message, 'expired_access_token')
                ),
            'slack' => str_contains($message, 'slack')
                && (
                    str_contains($message, 'not connected')
                    || str_contains($message, "n'est pas connecte")
                    || str_contains($message, 'n est pas connecte')
                    || str_contains($message, 'bot token')
                    || str_contains($message, 'reconnect')
                    || str_contains($message, 'invalid_auth')
                    || str_contains($message, 'token_revoked')
                    || str_contains($message, 'account_inactive')
                ),
            default => AutomationReconnectResolver::messageRequiresReconnect($message),
        };
    }

    protected function loadClient(int $tenantId, int $clientId): Client
    {
        $client = Client::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($clientId)
            ->first();

        if (!$client) {
            throw new RuntimeException(__('automation::automation.actions.client_missing'));
        }

        return $client;
    }

    protected function loadInvoice(int $tenantId, int $invoiceId): Invoice
    {
        $invoice = Invoice::query()
            ->with(['client'])
            ->where('tenant_id', $tenantId)
            ->whereKey($invoiceId)
            ->first();

        if (!$invoice) {
            throw new RuntimeException(__('automation::automation.actions.invoice_missing'));
        }

        return $invoice;
    }

    protected function loadQuote(int $tenantId, int $quoteId): Quote
    {
        $quote = Quote::query()
            ->with(['client'])
            ->where('tenant_id', $tenantId)
            ->whereKey($quoteId)
            ->first();

        if (!$quote) {
            throw new RuntimeException(__('automation::automation.actions.quote_missing'));
        }

        return $quote;
    }

    protected function loadStockOrder(int $tenantId, int $orderId): Order
    {
        $order = Order::query()
            ->with(['supplier', 'items.article', 'deliveryNotes'])
            ->where('tenant_id', $tenantId)
            ->whereKey($orderId)
            ->first();

        if (!$order) {
            throw new RuntimeException(__('automation::automation.actions.stock_order_missing'));
        }

        return $order;
    }

    protected function loadArticle(int $tenantId, int $articleId): Article
    {
        $article = Article::query()
            ->with(['supplier', 'movements'])
            ->withCurrentStock()
            ->where('tenant_id', $tenantId)
            ->whereKey($articleId)
            ->first();

        if (!$article) {
            throw new RuntimeException(__('automation::automation.actions.article_missing'));
        }

        return $article;
    }

    protected function loadSupplier(int $tenantId, int $supplierId): Supplier
    {
        $supplier = Supplier::query()
            ->with(['articles', 'orders', 'deliveryNotes'])
            ->where('tenant_id', $tenantId)
            ->whereKey($supplierId)
            ->first();

        if (!$supplier) {
            throw new RuntimeException(__('automation::automation.actions.supplier_missing'));
        }

        return $supplier;
    }

    protected function loadDeliveryNote(int $tenantId, int $deliveryNoteId): DeliveryNote
    {
        $deliveryNote = DeliveryNote::query()
            ->with(['supplier', 'client', 'order', 'invoice', 'items.article'])
            ->where('tenant_id', $tenantId)
            ->whereKey($deliveryNoteId)
            ->first();

        if (!$deliveryNote) {
            throw new RuntimeException(__('automation::automation.actions.delivery_note_missing'));
        }

        return $deliveryNote;
    }

    protected function loadProject(int $tenantId, int $projectId): Project
    {
        $project = Project::query()
            ->with(['client', 'owner', 'members'])
            ->where('tenant_id', $tenantId)
            ->whereKey($projectId)
            ->first();

        if (!$project) {
            throw new RuntimeException(__('automation::automation.actions.project_missing'));
        }

        return $project;
    }

    protected function loadProjectTask(int $tenantId, int $taskId): ProjectTask
    {
        $task = ProjectTask::query()
            ->with(['project.client', 'project.owner', 'assignee', 'creator', 'client'])
            ->where('tenant_id', $tenantId)
            ->whereKey($taskId)
            ->first();

        if (!$task) {
            throw new RuntimeException(__('automation::automation.actions.task_missing'));
        }

        return $task;
    }

    protected function loadInvitation(int $tenantId, int $invitationId): UserInvitation
    {
        $invitation = UserInvitation::query()
            ->with(['tenant', 'invitedBy', 'role', 'invitedUser'])
            ->where('tenant_id', $tenantId)
            ->whereKey($invitationId)
            ->first();

        if (!$invitation) {
            throw new RuntimeException(__('automation::automation.actions.invitation_missing'));
        }

        return $invitation;
    }

    protected function loadTenantExtension(int $tenantId, int $activationId): TenantExtension
    {
        $activation = TenantExtension::query()
            ->with(['extension'])
            ->where('tenant_id', $tenantId)
            ->whereKey($activationId)
            ->first();

        if (!$activation) {
            throw new RuntimeException(__('automation::automation.actions.activation_missing'));
        }

        return $activation;
    }

    protected function defaultCurrency(): string
    {
        return (string) config('crm-core.formats.currency', 'EUR');
    }

    protected function appName(): string
    {
        return trim((string) config('app.name', 'CRM'));
    }

    protected function routeUrl(string $routeName, mixed ...$parameters): ?string
    {
        if (!Route::has($routeName)) {
            return null;
        }

        if (count($parameters) === 0) {
            return route($routeName);
        }

        if (count($parameters) === 1) {
            return route($routeName, $parameters[0]);
        }

        return route($routeName, $parameters);
    }

    protected function formatMoney(float|int|string|null $amount, string $currency): string
    {
        return number_format((float) $amount, 2, ',', ' ') . ' ' . strtoupper($currency ?: $this->defaultCurrency());
    }

    protected function sanitizeText(?string $value): string
    {
        $clean = strip_tags((string) $value);
        $clean = preg_replace('/\s+/', ' ', $clean) ?? '';

        return trim($clean);
    }

    protected function clientDisplayName(Client $client): string
    {
        return trim((string) ($client->company_name ?: $client->contact_name ?: 'client'));
    }

    protected function projectMetadata(Project $project, string $key, mixed $default = null): mixed
    {
        $metadata = is_array($project->metadata) ? $project->metadata : [];

        return $metadata[$key] ?? $default;
    }

    protected function updateProjectMetadata(Project $project, string $key, mixed $value): void
    {
        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $metadata[$key] = $value;

        $project->update(['metadata' => $metadata]);
    }

    protected function logProjectActivity(
        int $tenantId,
        Project $project,
        ?ProjectTask $task,
        string $event,
        string $description,
        array $payload = [],
        ?int $userId = null
    ): void {
        ProjectActivity::query()->create([
            'tenant_id' => $tenantId,
            'project_id' => (int) $project->id,
            'project_task_id' => $task?->id ? (int) $task->id : null,
            'user_id' => $userId,
            'event' => $event,
            'description' => $description,
            'payload' => $payload,
        ]);
    }

    protected function defaultTaskStatus(): string
    {
        $statuses = (array) config('projects.task_statuses', []);

        if (array_key_exists('todo', $statuses)) {
            return 'todo';
        }

        $firstKey = array_key_first($statuses);

        return is_string($firstKey) && $firstKey !== '' ? $firstKey : 'todo';
    }

    protected function resolveClientProjectOrAutomationProject(
        int $tenantId,
        ?int $clientId,
        User $actor,
        string $fallbackName,
        string $bucket,
        ?string $description = null
    ): Project {
        if ($clientId) {
            $project = Project::query()
                ->where('tenant_id', $tenantId)
                ->where('client_id', $clientId)
                ->whereIn('status', ['planning', 'active', 'on_hold'])
                ->orderByDesc('updated_at')
                ->first();

            if ($project) {
                return $project;
            }
        }

        return $this->findOrCreateAutomationProject($tenantId, $actor, $fallbackName, $bucket, $description);
    }

    protected function findOrCreateAutomationProject(
        int $tenantId,
        User $actor,
        string $name,
        string $bucket,
        ?string $description = null
    ): Project {
        $project = Project::query()
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        if ($project) {
            ProjectMember::query()->updateOrCreate(
                ['project_id' => (int) $project->id, 'user_id' => (int) $actor->id],
                [
                    'tenant_id' => $tenantId,
                    'role' => 'owner',
                    'is_active' => true,
                    'invited_by' => (int) $actor->id,
                    'joined_at' => now(),
                ]
            );

            return $project;
        }

        $project = Project::query()->create([
            'tenant_id' => $tenantId,
            'client_id' => null,
            'owner_id' => (int) $actor->id,
            'name' => $name,
            'slug' => $this->uniqueProjectSlug($tenantId, $bucket),
            'description' => $description ?: 'Projet généré automatiquement par le moteur d’automation.',
            'status' => 'active',
            'priority' => 'medium',
            'start_date' => now()->toDateString(),
            'progress' => 0,
            'metadata' => [
                'automation_bucket' => $bucket,
                'automation_managed' => true,
            ],
        ]);

        ProjectMember::query()->updateOrCreate(
            ['project_id' => (int) $project->id, 'user_id' => (int) $actor->id],
            [
                'tenant_id' => $tenantId,
                'role' => 'owner',
                'is_active' => true,
                'invited_by' => (int) $actor->id,
                'joined_at' => now(),
            ]
        );

        $this->logProjectActivity(
            $tenantId,
            $project,
            null,
            'automation_project_created',
            __('automation::automation.actions.project_automation_created'),
            ['bucket' => $bucket],
            (int) $actor->id
        );

        return $project;
    }

    protected function uniqueProjectSlug(int $tenantId, string $stem): string
    {
        $base = Str::slug($stem);
        $base = $base !== '' ? $base : 'automation-project';
        $base = Str::limit($base, 180, '');
        $slug = $base;
        $suffix = 1;

        while (
            Project::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->exists()
        ) {
            $candidateBase = Str::limit($base, 175, '');
            $slug = $candidateBase . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function sourceUrlForModel(Model $model): ?string
    {
        return match ($model::class) {
            Client::class => $this->routeUrl('clients.show', $model),
            Invoice::class => $this->routeUrl('invoices.show', $model),
            Quote::class => $this->routeUrl('invoices.quotes.show', $model),
            Project::class => $this->routeUrl('projects.show', $model),
            Supplier::class => $this->routeUrl('stock.suppliers.show', $model),
            Article::class => $this->routeUrl('stock.articles.show', $model),
            Order::class => $this->routeUrl('stock.orders.show', $model),
            DeliveryNote::class => $this->routeUrl('stock.delivery-notes.show', $model),
            default => null,
        };
    }
}
