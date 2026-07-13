<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        $methods = PaymentMethod::orderBy('sort_order')->orderBy('id')->get();

        return view('superadmin.payment-methods.index', compact('methods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'provider' => ['required', 'string', 'in:paypal,manual,stripe'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $method = PaymentMethod::create([
            'name' => $data['name'],
            'provider' => $data['provider'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_default' => false,
            'config' => [],
        ]);

        if (! empty($data['is_default']) || PaymentMethod::count() === 1) {
            $method->markAsDefault();
        }

        return back()->with('success', __('billing.payments.created'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $paymentMethod->update([
            'name' => $data['name'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', __('billing.payments.updated'));
    }

    public function toggle(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        return back()->with('success', __('billing.payments.updated'));
    }

    public function setDefault(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->markAsDefault();

        return back()->with('success', __('billing.payments.default_set'));
    }

    /**
     * Teste la configuration du moyen de paiement.
     * Ne fait AUCun appel avec de vraies clés : vérifie seulement que la
     * configuration attendue (dans .env / config/services) est présente et
     * cohérente. Un appel API réel se branche en phase 2 (mode sandbox).
     */
    public function test(PaymentMethod $paymentMethod): JsonResponse
    {
        $ok = match ($paymentMethod->provider) {
            'manual' => true,
            'paypal' => filled(config('services.paypal.client_id')) && filled(config('services.paypal.client_secret')),
            'stripe' => filled(config('services.stripe.secret')),
            default => false,
        };

        return response()->json([
            'success' => $ok,
            'message' => $ok ? __('billing.payments.test_ok') : __('billing.payments.test_failed'),
        ], $ok ? 200 : 422);
    }
}
