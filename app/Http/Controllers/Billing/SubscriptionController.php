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

    /** Étape 1 : choix du forfait. */
    public function plans(Request $request): View
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $plans = SubscriptionPlan::active()
            ->with(['prices' => fn ($q) => $q->where('is_active', true)->orderBy('period_months')])
            ->orderBy('sort_order')->orderBy('id')->get();

        return view('billing.plans', [
            'plans' => $plans,
            'hasUsedTrial' => $this->subscriptions->hasUsedTrial((int) $tenant->id),
            'current' => $this->subscriptions->activeSubscription((int) $tenant->id),
        ]);
    }

    /** Forfait gratuit / démo (prix 0) : activation immédiate -> tableau de bord. */
    public function activateFree(Request $request): RedirectResponse
    {
        $data = $request->validate(['plan' => ['required', 'string']]);
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $plan = SubscriptionPlan::active()->where('uuid', $data['plan'])->firstOrFail();
        abort_unless($this->planIsFree($plan), 422);

        if ($this->subscriptions->hasUsedTrial((int) $tenant->id)) {
            return back()->withErrors(['plan' => __('billing.onboarding.trial_once_used')]);
        }

        $this->subscriptions->startTrial($tenant, $plan);

        return redirect()->route('dashboard')->with('success', __('billing.onboarding.trial_success'));
    }

    /** Étape 2 : page de paiement (choix du moyen de paiement) pour un forfait payant. */
    public function checkout(Request $request): View|RedirectResponse
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $plan = SubscriptionPlan::active()->where('uuid', (string) $request->query('plan'))->firstOrFail();
        $price = SubscriptionPlanPrice::where('plan_id', $plan->id)
            ->where('id', (int) $request->query('plan_price_id'))
            ->where('is_active', true)
            ->first();

        if (! $price) {
            return redirect()->route('subscription.plans');
        }

        $methods = PaymentMethod::active()->orderByDesc('is_default')->orderBy('sort_order')->get();

        return view('billing.checkout', compact('plan', 'price', 'methods'));
    }

    /** Étape 3 : initier le paiement. */
    public function pay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'string'],
            'plan_price_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string'],
        ]);

        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $plan = SubscriptionPlan::active()->where('uuid', $data['plan'])->firstOrFail();
        $price = SubscriptionPlanPrice::where('plan_id', $plan->id)
            ->where('id', (int) $data['plan_price_id'])->where('is_active', true)->firstOrFail();

        $provider = $data['payment_method'];

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
        $subscription = $this->subscriptions->activatePaid($tenant, $plan, $price, $provider, ['channel' => $provider]);
        $request->session()->flash('billing.activated', $subscription->id);

        return redirect()->route('subscription.success');
    }

    /** Retour PayPal : capture du paiement puis page de succès. */
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
        $subscription = $this->subscriptions->activatePaid($tenant, $plan, $price, 'paypal', ['paypal_order' => $orderId]);

        $request->session()->flash('billing.activated', $subscription->id);

        return redirect()->route('subscription.success');
    }

    public function paypalCancel(Request $request): RedirectResponse
    {
        $request->session()->forget('billing.pending');

        return redirect()->route('subscription.plans');
    }

    /** Étape 4 : page de succès (facture envoyée par e-mail + bouton tableau de bord). */
    public function success(Request $request): View|RedirectResponse
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $subscription = $this->subscriptions->activeSubscription((int) $tenant->id);

        if (! $subscription) {
            return redirect()->route('subscription.plans');
        }

        return view('billing.success', compact('subscription'));
    }

    private function planIsFree(SubscriptionPlan $plan): bool
    {
        return (bool) $plan->is_free || (float) $plan->monthly_price <= 0;
    }
}
