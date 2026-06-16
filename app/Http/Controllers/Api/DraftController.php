<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class DraftController extends Controller
{
    public function __construct(protected DraftService $draftService) {}

    public function save(Request $request): JsonResponse
    {
        try {
            $payload = $request->validate([
                'type' => ['required', 'string', 'max:120'],
                'route' => ['nullable', 'string', 'max:500'],
                'data' => ['nullable', 'array'],
            ]);

            $draft = $this->draftService->saveForCurrentActor($payload);

            return response()->json([
                'success' => true,
                'message' => 'Brouillon sauvegarde.',
                'data' => $this->draftService->formatDraft($draft),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de sauvegarder le brouillon pour le moment.',
            ], 500);
        }
    }

    public function load(Request $request): JsonResponse
    {
        try {
            $payload = $request->validate([
                'type' => ['required', 'string', 'max:120'],
                'route' => ['nullable', 'string', 'max:500'],
            ]);

            $draft = $this->draftService->loadLatestForCurrentActor(
                (string) $payload['type'],
                $payload['route'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $draft ? $this->draftService->formatDraft($draft) : null,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 503);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de charger le brouillon pour le moment.',
                'data' => null,
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->draftService->deleteForCurrentActor($id);

            return response()->json([
                'success' => true,
                'message' => $deleted ? 'Brouillon supprime.' : 'Brouillon deja supprime.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer le brouillon pour le moment.',
            ], 500);
        }
    }
}
