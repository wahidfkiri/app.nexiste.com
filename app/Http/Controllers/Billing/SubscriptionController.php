<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use App\Services\Billing\PaymentManager;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptions,
        private PaymentManager $payments,
    ) {
    }

    public function plans(Request $request): View
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $plans = SubscriptionPlan::active()
            ->with(['prices' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')->orderBy('id')->get();

        $methods = PaymentMethod::active()->orderByDesc('is_default')->orderBy('sort_order')->get();

        return view('billing.plans', [
            'plans' => $plans,
            'methods' => $methods,
            'hasUsedTrial' => $this->subscriptions->hasUsedTrial((int) $tenant->id),
            'current' => $this->subscriptions->activeSubscription((int) $tenant->id),
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'string'],
            'mode' => ['required', 'in:trial,paid'],
            'plan_price_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'string'],
        ]);

        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $plan = SubscriptionPlan::active()->where('uuid', $data['plan'])->firstOrFail();

        // Essai gratuit (une seule fois).
        if ($data['mode'] === 'trial') {
            if ($this->subscriptions->hasUsedTrial((int) $tenant->id)) {
                return back()->withErrors(['plan' => __('billing.onboarding.trial_once_used')]);
            }
            $this->subscriptions->startTrial($tenant, $plan);

            return redirect()->route('dashboard')->with('success', __('billing.onboarding.trial_success'));
        }

        // Abonnement payant : période + moyen de paiement obligatoires.
        $price = SubscriptionPlanPrice::where('plan_id', $plan->id)
            ->where('id', (int) ($data['plan_price_id'] ?? 0))
            ->where('is_active', true)
            ->firstOrFail();

        $provider = (string) ($data['payment_method'] ?? 'manual');

        if ($provider === 'paypal') {
            try {
                $order = $this->payments->createPaypalOrder(
                    (float) $price->price,
                    (string) $plan->currency,
                    $plan->name . ' — ' . $price->period_months . ' ' . __('billing.common.months'),
                    route('subscription.paypal.return'),
                    route('subscription.paypal.cancel'),
                );
            } catch (\Throwable $e) {
                return back()->withErrors(['payment_method' => $e->getMessage()]);
            }

            $request->session()->put('billing.pending', [
                'plan_id' => $plan->id,
                'price_id' => $price->id,
                'order_id' => $order['order_id'],
            ]);

            return redirect()->away($order['approval_url']);
        }

        // Paiement manuel / hors-ligne : activation immédiate.
        $this->subscriptions->activatePaid($tenant, $plan, $price, $provider, ['channel' => $provider]);

        return redirect()->route('dashboard')->with('success', __('billing.onboarding.success'));
    }

    public function paypalReturn(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $pending = (array) $request->session()->pull('billing.pending', []);
        $orderId = (string) ($request->query('token') ?: ($pending['order_id'] ?? ''));

        if (! $tenant || ! $orderId || ($pending['order_id'] ?? null) !== $orderId) {
            return redirect()->route('subscription.plans')->withErrors(['payment_method' => __('billing.payments.test_failed')]);
        }

        try {
            if (! $this->payments->capturePaypalOrder($orderId)) {
                throw new \RuntimeException(__('billing.payments.test_failed'));
            }
        } catch (\Throwable $e) {
            return redirect()->route('subscription.plans')->withErrors(['payment_method' => $e->getMessage()]);
        }

        $plan = SubscriptionPlan::findOrFail($pending['plan_id']);
        $price = SubscriptionPlanPrice::findOrFail($pending['price_id']);
        $this->subscriptions->activatePaid($tenant, $plan, $price, 'paypal', ['paypal_order' => $orderId]);

        return redirect()->route('dashboard')->with('success', __('billing.onboarding.success'));
    }

    public function paypalCancel(Request $request): RedirectResponse
    {
        $request->session()->forget('billing.pending');

        return redirect()->route('subscription.plans');
    }
}
