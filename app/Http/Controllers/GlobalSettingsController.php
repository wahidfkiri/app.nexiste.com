<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateGlobalSettingsRequest;
use App\Http\Requests\Settings\StartGlobalDataExportRequest;
use App\Models\TenantDataExport;
use App\Services\DataExports\TenantDataExportService;
use App\Support\Security\PhoneNumberService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use NexusExtensions\Dropbox\Models\DropboxToken;
use NexusExtensions\GoogleDocx\Models\GoogleDocxToken;
use NexusExtensions\GoogleDrive\Models\GoogleDriveToken;
use NexusExtensions\GoogleGmail\Models\GoogleGmailToken;
use NexusExtensions\GoogleMeet\Models\GoogleMeetToken;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsToken;
use NexusExtensions\NotionWorkspace\Models\NotionWorkspaceToken;
use NexusExtensions\Slack\Models\SlackToken;
use Vendor\CrmCore\Models\TenantSetting;
use Vendor\GoogleCalendar\Models\GoogleCalendarToken;

class GlobalSettingsController extends Controller
{
    private const SETTING_KEYS = [
        'company_country',
        'company_postal_code',
        'company_city',
        'company_website',
        'company_description',
        'business_hours_start',
        'business_hours_end',
        'invoice_prefix',
        'default_tax_rate',
        'date_format',
        'notifications_email',
        'notifications_browser',
        'automation_suggestions_enabled',
    ];

    public function __construct(
        protected PhoneNumberService $phoneNumbers,
        protected TenantDataExportService $dataExports
    ) {
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $tenant = $user?->tenant;
        $isSuperAdmin = $this->isSuperAdmin($user);

        abort_unless($tenant, 404);

        $settings = TenantSetting::query()
            ->where('tenant_id', (int) $tenant->id)
            ->whereIn('key', self::SETTING_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        $countries = collect((array) config('onboarding.countries', []))
            ->filter(fn ($country) => !empty($country['code']))
            ->map(function ($country) {
                return [
                    'code' => strtoupper((string) ($country['code'] ?? '')),
                    'name' => (string) ($country['name'] ?? ''),
                    'dial' => (string) ($country['dial'] ?? ''),
                ];
            })
            ->values()
            ->all();

        $latestExport = $this->dataExports->latestForUser($user);
        $currentDataExport = $latestExport
            ? $this->dataExports->present($latestExport, $user)
            : null;

        if (($currentDataExport['status'] ?? null) === 'completed') {
            $currentDataExport = null;
        }

        return view('settings.global', [
            'tenant' => $tenant,
            'settings' => $settings,
            'countries' => $countries,
            'currencies' => (array) config('onboarding.currencies', []),
            'timezones' => timezone_identifiers_list(),
            'canManageTenant' => method_exists($user, 'canManageTenant') ? $user->canManageTenant() : false,
            'isSuperAdmin' => $isSuperAdmin,
            'backupProviders' => $this->dataExports->providerStates((int) $tenant->id),
            'currentDataExport' => $currentDataExport,
            'dataExportHistory' => $this->dataExports->historyForUser($user),
            'oauthSessionInsights' => $isSuperAdmin ? $this->oauthSessionInsights((int) $tenant->id) : [],
        ]);
    }

    public function update(UpdateGlobalSettingsRequest $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $tenant = $user?->tenant;

        if (!$tenant) {
            return $this->errorResponse($request, 'Tenant introuvable.', 422);
        }

        $data = $request->validated();
        $phone = trim((string) ($data['tenant_phone'] ?? ''));
        $normalizedPhone = $phone !== ''
            ? ($this->phoneNumbers->normalizeInternational($phone) ?? $phone)
            : null;

        $tenant->update([
            'name' => (string) $data['tenant_name'],
            'email' => (string) ($data['tenant_email'] ?? ''),
            'phone' => $normalizedPhone,
            'address' => (string) ($data['tenant_address'] ?? ''),
            'timezone' => (string) $data['tenant_timezone'],
            'locale' => (string) $data['tenant_locale'],
            'currency' => strtoupper((string) $data['tenant_currency']),
        ]);

        $toSave = [
            'company_country' => (string) ($data['company_country'] ?? ''),
            'company_postal_code' => (string) ($data['company_postal_code'] ?? ''),
            'company_city' => (string) ($data['company_city'] ?? ''),
            'company_website' => (string) ($data['company_website'] ?? ''),
            'company_description' => (string) ($data['company_description'] ?? ''),
            'business_hours_start' => (string) ($data['business_hours_start'] ?? ''),
            'business_hours_end' => (string) ($data['business_hours_end'] ?? ''),
            'invoice_prefix' => strtoupper((string) ($data['invoice_prefix'] ?? '')),
            'default_tax_rate' => isset($data['default_tax_rate']) ? (string) $data['default_tax_rate'] : '',
            'date_format' => (string) ($data['date_format'] ?? 'd/m/Y'),
            'notifications_email' => (string) ((int) filter_var($data['notifications_email'] ?? false, FILTER_VALIDATE_BOOL)),
            'notifications_browser' => (string) ((int) filter_var($data['notifications_browser'] ?? false, FILTER_VALIDATE_BOOL)),
            'automation_suggestions_enabled' => (string) ((int) filter_var($data['automation_suggestions_enabled'] ?? false, FILTER_VALIDATE_BOOL)),
        ];

        foreach ($toSave as $key => $value) {
            TenantSetting::query()->updateOrCreate(
                ['tenant_id' => (int) $tenant->id, 'key' => $key],
                ['value' => $value]
            );
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Paramètres globaux enregistrés avec succès.',
            ]);
        }

        return redirect()
            ->route('settings.global')
            ->with('success', 'Paramètres globaux enregistrés avec succès.');
    }

    private function errorResponse(Request $request, string $message, int $status): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()->withErrors(['settings' => $message])->withInput();
    }

    public function startDataExport(StartGlobalDataExportRequest $request): JsonResponse
    {
        try {
            $export = $this->dataExports->start($request->user(), (string) $request->validated('provider'));

            return response()->json([
                'success' => true,
                'message' => 'Sauvegarde preparee. La generation detaillee commence maintenant.',
                'data' => $this->dataExports->present($export, $request->user()),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $this->dataExports->publicErrorMessage($e->getMessage()),
            ], 422);
        }
    }

    public function processDataExport(Request $request, TenantDataExport $dataExport): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && method_exists($user, 'canManageTenant') && $user->canManageTenant(), 403);

        try {
            $export = $this->dataExports->advance($dataExport, $user);

            return response()->json([
                'success' => true,
                'message' => $export->status === 'completed'
                    ? 'Sauvegarde terminee et archive envoyee avec succes.'
                    : 'Etape de sauvegarde traitee.',
                'data' => $this->dataExports->present($export, $user),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $this->dataExports->publicErrorMessage($e->getMessage()),
            ], 422);
        }
    }

    public function showDataExport(Request $request, TenantDataExport $dataExport): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && method_exists($user, 'canManageTenant') && $user->canManageTenant(), 403);

        return response()->json([
            'success' => true,
            'data' => $this->dataExports->present($dataExport, $user),
        ]);
    }

    private function isSuperAdmin(mixed $user): bool
    {
        if (!$user || !method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole('super_admin') || $user->hasRole('super-admin');
    }

    private function oauthSessionInsights(int $tenantId): array
    {
        $definitions = [
            [
                'label' => 'Google Calendar',
                'model' => GoogleCalendarToken::class,
                'nominal_lifetime' => 'Environ 1 heure',
                'refresh_buffer' => 300,
                'tracks_expiry' => true,
                'supports_refresh_token' => true,
                'strategy' => 'Refresh automatique 5 minutes avant expiration.',
                'note' => 'Reconnexion seulement si le refresh token devient invalide.',
            ],
            [
                'label' => 'Google Gmail',
                'model' => GoogleGmailToken::class,
                'nominal_lifetime' => 'Environ 1 heure',
                'refresh_buffer' => 300,
                'tracks_expiry' => true,
                'supports_refresh_token' => true,
                'strategy' => 'Refresh automatique 5 minutes avant expiration.',
                'note' => 'Le compte reste connecte tant que Google accepte le refresh token.',
            ],
            [
                'label' => 'Google Drive',
                'model' => GoogleDriveToken::class,
                'nominal_lifetime' => 'Environ 1 heure',
                'refresh_buffer' => 300,
                'tracks_expiry' => true,
                'supports_refresh_token' => true,
                'strategy' => 'Refresh automatique 5 minutes avant expiration.',
                'note' => 'Pratique pour les exports cloud et les liens de reprise.',
            ],
            [
                'label' => 'Google Docs',
                'model' => GoogleDocxToken::class,
                'nominal_lifetime' => 'Environ 1 heure',
                'refresh_buffer' => 300,
                'tracks_expiry' => true,
                'supports_refresh_token' => true,
                'strategy' => 'Refresh automatique 5 minutes avant expiration.',
                'note' => 'Le module relance la session avant de manipuler les documents.',
            ],
            [
                'label' => 'Google Sheets',
                'model' => GoogleSheetsToken::class,
                'nominal_lifetime' => 'Environ 1 heure',
                'refresh_buffer' => 300,
                'tracks_expiry' => true,
                'supports_refresh_token' => true,
                'strategy' => 'Refresh automatique 5 minutes avant expiration.',
                'note' => 'Le compte reste actif tant que le refresh token Google est valable.',
            ],
            [
                'label' => 'Google Meet',
                'model' => GoogleMeetToken::class,
                'nominal_lifetime' => 'Environ 1 heure',
                'refresh_buffer' => 300,
                'tracks_expiry' => true,
                'supports_refresh_token' => true,
                'strategy' => 'Refresh automatique 5 minutes avant expiration.',
                'note' => 'Aligne sa logique de session sur Google Calendar.',
            ],
            [
                'label' => 'Dropbox',
                'model' => DropboxToken::class,
                'nominal_lifetime' => 'Environ 1 heure',
                'refresh_buffer' => 300,
                'tracks_expiry' => true,
                'supports_refresh_token' => true,
                'strategy' => 'Refresh automatique 5 minutes avant expiration.',
                'note' => 'La session reste transparente tant que Dropbox accepte le refresh token.',
            ],
            [
                'label' => 'Notion',
                'model' => NotionWorkspaceToken::class,
                'nominal_lifetime' => 'Selon expires_in renvoye par Notion',
                'refresh_buffer' => 300,
                'tracks_expiry' => true,
                'supports_refresh_token' => true,
                'strategy' => 'Refresh automatique 5 minutes avant expiration, puis reessai sur 401 si besoin.',
                'note' => 'Le refresh token est bien utilise dans ce module.',
            ],
            [
                'label' => 'Slack',
                'model' => SlackToken::class,
                'nominal_lifetime' => 'Non suivi localement',
                'refresh_buffer' => 0,
                'tracks_expiry' => false,
                'supports_refresh_token' => false,
                'strategy' => 'Pas de refresh token dans ce flow. Le bot token reste actif jusqu a revocation ou deconnexion.',
                'note' => 'Si Slack invalide le bot token, une reconnexion manuelle est necessaire.',
            ],
        ];

        return collect($definitions)
            ->map(fn (array $definition) => $this->buildOauthSessionInsight($definition, $tenantId))
            ->all();
    }

    private function buildOauthSessionInsight(array $definition, int $tenantId): array
    {
        $token = $this->resolveOauthToken($definition['model'], $tenantId);
        $tracksExpiry = (bool) ($definition['tracks_expiry'] ?? false);
        $refreshBuffer = (int) ($definition['refresh_buffer'] ?? 0);
        $expiresAt = $token?->token_expires_at;
        $hasRefreshToken = (bool) ($token && property_exists($token, 'refresh_token') ? $token->refresh_token : data_get($token, 'refresh_token'));
        $isExpired = $tracksExpiry && $token ? $this->tokenIsExpired($token, $refreshBuffer) : false;

        return [
            'label' => (string) $definition['label'],
            'nominal_lifetime' => (string) $definition['nominal_lifetime'],
            'refresh_buffer_label' => $refreshBuffer > 0 ? ($refreshBuffer / 60) . ' min' : 'Aucun',
            'strategy' => (string) $definition['strategy'],
            'note' => (string) $definition['note'],
            'status_label' => !$token
                ? 'Non connecte'
                : ($isExpired ? 'Refresh requis' : 'Connecte'),
            'status_tone' => !$token
                ? 'idle'
                : ($isExpired ? 'warning' : 'ready'),
            'connected_identity' => $this->tokenIdentity($token),
            'expires_at_label' => !$tracksExpiry
                ? 'Non suivi dans ce module'
                : ($expiresAt ? $expiresAt->copy()->timezone(config('app.timezone'))->format('d/m/Y H:i') : 'Aucune date renvoyee'),
            'refresh_token_label' => !($definition['supports_refresh_token'] ?? false)
                ? 'Non'
                : ($token ? ($hasRefreshToken ? 'Oui' : 'Non') : 'Oui, si une session est connectee'),
        ];
    }

    private function resolveOauthToken(string $modelClass, int $tenantId): ?Model
    {
        if (!class_exists($modelClass)) {
            return null;
        }

        /** @var Model $instance */
        $instance = new $modelClass();
        $table = $instance->getTable();

        if (!Schema::hasTable($table)) {
            return null;
        }

        $query = $modelClass::query()->where('tenant_id', $tenantId);

        if (Schema::hasColumn($table, 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->latest('id')->first();
    }

    private function tokenIsExpired(Model $token, int $refreshBuffer): bool
    {
        $expiresAt = $token->token_expires_at ?? null;

        if (!$expiresAt) {
            return false;
        }

        return $expiresAt->copy()->subSeconds($refreshBuffer)->isPast();
    }

    private function tokenIdentity(?Model $token): string
    {
        if (!$token) {
            return 'Aucune session active';
        }

        foreach ([
            'google_email',
            'dropbox_email',
            'notion_user_email',
            'team_name',
            'google_name',
            'dropbox_name',
            'notion_workspace_name',
        ] as $field) {
            $value = trim((string) data_get($token, $field, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'Session active sans identite lisible';
    }
}
