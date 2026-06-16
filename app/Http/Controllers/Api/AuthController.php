<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Rbac\Services\TenantRoleService;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();

            if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect',
                ], 401);
            }

            /** @var User $user */
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();

                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est désactivé. Veuillez contacter l’administrateur.',
                    'code' => 'account_disabled',
                ], 403);
            }

            DB::beginTransaction();

            $token = $user->createToken('auth_token', ['*'])->plainTextToken;
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            DB::commit();

            $tenantRole = $user->tenantRole($user->tenant_id);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company' => $user->company,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'position' => $user->position,
                    'is_active' => $user->is_active,
                    'initials' => $user->initials,
                    'role' => $tenantRole?->name ?? $user->role_in_tenant,
                    'permissions' => $tenantRole?->permissions?->pluck('name') ?? collect(),
                ],
                'redirect' => '/dashboard',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Login error: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la connexion. Veuillez réessayer.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->validated();

            DB::beginTransaction();

            $user = User::create([
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => mb_strtolower((string) $data['email']),
                'company' => !empty($data['company']) ? $data['company'] : null,
                'password' => Hash::make($data['password']),
                'is_active' => true,
                'status' => 'active',
            ]);

            $tenantName = !empty($data['company']) ? $data['company'] : ('Espace de ' . $data['first_name']);
            $tenant = Tenant::create([
                'name' => $tenantName,
                'slug' => Tenant::generateSlug($tenantName),
                'email' => $user->email,
                'timezone' => 'Europe/Paris',
                'locale' => 'fr',
                'currency' => 'EUR',
                'status' => 'active',
            ]);

            $user->forceFill([
                'tenant_id' => (int) $tenant->id,
                'role_in_tenant' => 'owner',
                'is_tenant_owner' => true,
            ])->save();

            app(TenantRoleService::class)->syncUserRole($user, (int) $tenant->id, 'owner', [
                'is_tenant_owner' => true,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            event(new Registered($user));

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'company' => $user->company,
                    'is_active' => $user->is_active,
                    'initials' => $user->initials,
                    'role' => 'owner',
                ],
                'redirect' => '/dashboard',
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Registration error: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString(),
            ]);

            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet email est déjà utilisé.',
                    'errors' => ['email' => ['Cet email est déjà utilisé.']],
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l’inscription.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc'],
        ], [
            'email.required' => 'L email est obligatoire.',
            'email.email' => 'Le format de l email est invalide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Merci de corriger les champs signales.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink([
            'email' => mb_strtolower((string) $validator->validated()['email']),
        ]);

        if ($status === Password::RESET_THROTTLED) {
            $message = 'Un email de reinitialisation a deja ete envoye recemment. Merci de patienter un moment.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    'email' => [$message],
                ],
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => 'Si un compte existe avec cette adresse email, un lien de reinitialisation vient d etre envoye.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
        ], [
            'token.required' => 'Le jeton de reinitialisation est obligatoire.',
            'email.required' => 'L email est obligatoire.',
            'email.email' => 'Le format de l email est invalide.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caracteres.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.regex' => 'Le mot de passe doit contenir une minuscule, une majuscule, un chiffre et un caractere special.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Merci de corriger les champs signales.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $status = Password::reset(
            [
                'email' => mb_strtolower((string) $validated['email']),
                'password' => (string) $validated['password'],
                'password_confirmation' => (string) $request->input('password_confirmation', ''),
                'token' => (string) $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $message = match ($status) {
                Password::INVALID_TOKEN => 'Le lien de reinitialisation est invalide ou expire.',
                Password::INVALID_USER => 'Ce compte est introuvable.',
                default => 'La reinitialisation du mot de passe a echoue. Merci de reessayer.',
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    'email' => [$message],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a ete reinitialise. Vous pouvez maintenant vous connecter.',
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                $user->currentAccessToken()?->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie',
            ]);
        } catch (Throwable $e) {
            Log::error('Logout error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
            ], 500);
        }
    }

    public function user(Request $request)
    {
        try {
            /** @var User|null $user */
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié',
                ], 401);
            }

            $tenantRole = $user->tenantRole($user->tenant_id);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'company' => $user->company,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'position' => $user->position,
                    'bio' => $user->bio,
                    'is_active' => $user->is_active,
                    'initials' => $user->initials,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ],
                'roles' => $tenantRole ? collect([$tenantRole->name]) : collect(),
                'permissions' => $tenantRole?->permissions?->pluck('name') ?? collect(),
            ]);
        } catch (Throwable $e) {
            Log::error('Get user error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations',
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié',
                ], 401);
            }

            DB::beginTransaction();

            $user->currentAccessToken()?->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Token rafraîchi avec succès',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Token refresh error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement du token',
            ], 500);
        }
    }

    public function disableAccount(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            if (!$request->user()->hasAnyRole(['super_admin', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les droits pour désactiver un compte',
                ], 403);
            }

            DB::beginTransaction();

            $user->is_active = false;
            $user->save();
            $user->tokens()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compte désactivé avec succès',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                ],
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Disable account error: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation du compte',
            ], 500);
        }
    }

    public function enableAccount(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            if (!$request->user()->hasAnyRole(['super_admin', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les droits pour activer un compte',
                ], 403);
            }

            DB::beginTransaction();
            $user->is_active = true;
            $user->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compte activé avec succès',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                ],
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Enable account error: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’activation du compte',
            ], 500);
        }
    }
}
