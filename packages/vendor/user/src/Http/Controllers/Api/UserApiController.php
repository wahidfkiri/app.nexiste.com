<?php

namespace Vendor\User\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Vendor\User\Http\Requests\InviteRequest;
use Vendor\User\Http\Requests\UserRequest;
use Vendor\User\Services\UserService;

class UserApiController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->getFilteredUsers($request->all());

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        abort_if(!$user->hasTenantAccess((int) auth()->user()->tenant_id), 403, __('user::users.errors.access_denied'));

        return response()->json(['success' => true, 'data' => $user->load('roles')]);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        abort_if(!$user->hasTenantAccess((int) auth()->user()->tenant_id), 403, __('user::users.errors.access_denied'));

        try {
            $user = $this->userService->updateUser($user, $request->validated());
            return response()->json(['success' => true, 'data' => $user, 'message' => __('user::users.messages.updated')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if(!$user->hasTenantAccess((int) auth()->user()->tenant_id), 403, __('user::users.errors.access_denied'));

        try {
            $this->userService->deleteUser($user);
            return response()->json(['success' => true, 'message' => __('user::users.messages.deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function invite(InviteRequest $request): JsonResponse
    {
        try {
            $invitation = $this->userService->invite($request->validated());
            return response()->json([
                'success' => true,
                'data' => $invitation,
                'message' => __('user::users.messages.invited_to', ['email' => $invitation->email]),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function stats(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->userService->getStats()]);
    }
}