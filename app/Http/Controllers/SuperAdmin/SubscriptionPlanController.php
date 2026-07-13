<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::with('prices')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('superadmin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('superadmin.plans.form', ['plan' => new SubscriptionPlan(['currency' => 'EUR', 'is_active' => true])]);
    }

    public function edit(SubscriptionPlan $plan): View
    {
        $plan->load('prices');

        return view('superadmin.plans.form', compact('plan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $plan = SubscriptionPlan::create($this->planAttributes($data));
        $this->syncPrices($plan, $data);

        return redirect()->route('superadmin.plans.index')->with('success', __('billing.plans.created'));
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $this->validated($request, $plan);
        $plan->update($this->planAttributes($data));
        $this->syncPrices($plan, $data);

        return redirect()->route('superadmin.plans.index')->with('success', __('billing.plans.updated'));
    }

    public function toggle(SubscriptionPlan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('success', __('billing.plans.status_updated'));
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('superadmin.plans.index')->with('success', __('billing.plans.deleted'));
    }

    private function validated(Request $request, ?SubscriptionPlan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_free' => ['nullable', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'monthly_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
            'features' => ['nullable', 'string', 'max:4000'],
            'periods' => ['nullable', 'array'],
            'periods.*.period_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'periods.*.price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'periods.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function planAttributes(array $data): array
    {
        $features = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return [
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) ?: Str::random(8),
            'description' => $data['description'] ?? null,
            'is_free' => (bool) ($data['is_free'] ?? false),
            'trial_days' => (int) ($data['trial_days'] ?? 0),
            'monthly_price' => (float) ($data['monthly_price'] ?? 0),
            'currency' => strtoupper($data['currency']),
            'features' => $features,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    /**
     * Remplace les périodes du forfait. Le prix est recalculé automatiquement
     * si l'admin ne l'a pas saisi (mensuel × mois − remise), sinon on respecte
     * la valeur éditée manuellement.
     */
    private function syncPrices(SubscriptionPlan $plan, array $data): void
    {
        $plan->prices()->delete();

        foreach (($data['periods'] ?? []) as $row) {
            $months = (int) ($row['period_months'] ?? 0);
            if ($months < 1) {
                continue;
            }

            $discount = (float) ($row['discount_percent'] ?? 0);
            $price = isset($row['price']) && $row['price'] !== null && $row['price'] !== ''
                ? (float) $row['price']
                : SubscriptionPlan::computePeriodPrice((float) $plan->monthly_price, $months, $discount);

            $plan->prices()->updateOrCreate(
                ['period_months' => $months],
                ['price' => round($price, 2), 'discount_percent' => $discount, 'is_active' => true],
            );
        }
    }
}
