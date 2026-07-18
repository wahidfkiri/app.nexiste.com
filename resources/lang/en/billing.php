<?php

return [
    // Common
    'common' => [
        'month' => 'month',
        'months' => 'months',
        'free' => 'Free',
        'trial' => 'Trial',
        'per_month' => '/ month',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'default' => 'Default',
        'discount' => 'Discount',
        'price' => 'Price',
        'period' => 'Period',
        'currency' => 'Currency',
    ],

    // Administration — plans
    'plans' => [
        'title' => 'Subscription plans',
        'subtitle' => 'Create and manage plans, their periods, prices and discounts.',
        'add' => 'New plan',
        'name' => 'Plan name',
        'description' => 'Description',
        'monthly_price' => 'Reference monthly price',
        'monthly_price_hint' => 'Used for automatic calculation of periods.',
        'is_free' => 'Free plan (demo)',
        'trial_days' => 'Trial period duration (days)',
        'trial_days_hint' => 'The trial period can only be used once per workspace.',
        'features' => 'Highlights (one per line)',
        'periods' => 'Periods and prices',
        'periods_hint' => 'The price is calculated automatically (monthly × months − discount) and remains editable.',
        'period_months' => 'Number of months',
        'computed_price' => 'Computed price',
        'final_price' => 'Final price',
        'add_period' => 'Add a period',
        'empty' => 'No plan yet. Create your first plan.',
        'created' => 'Plan created successfully.',
        'updated' => 'Plan updated successfully.',
        'deleted' => 'Plan deleted.',
        'status_updated' => 'Plan status updated.',
    ],

    // Administration — payment methods
    'payments' => [
        'title' => 'Payment methods',
        'subtitle' => 'Configure the payment methods offered to clients.',
        'add' => 'New payment method',
        'name' => 'Label',
        'provider' => 'Provider',
        'provider_paypal' => 'PayPal',
        'provider_manual' => 'Manual payment / bank transfer',
        'provider_stripe' => 'Stripe',
        'set_default' => 'Set as default',
        'test' => 'Test connection',
        'test_ok' => 'Test successful: the payment method is correctly configured.',
        'test_failed' => 'Test failed. Check the configuration and API keys.',
        'created' => 'Payment method added.',
        'updated' => 'Payment method updated.',
        'default_set' => 'Payment method set as default.',
        'empty' => 'No payment method configured.',
        'keys_notice' => 'Secret (API) keys are never stored in the database: they are configured in the server .env file.',
    ],

    // Onboarding — plan selection
    'onboarding' => [
        'title' => 'Choose your plan',
        'subtitle' => 'Select the plan and period that suit you to activate your workspace.',
        'choose_period' => 'Choose the period',
        'start_trial' => 'Start free trial',
        'subscribe' => 'Subscribe',
        'trial_badge' => ':days-day trial',
        'trial_once_used' => 'The trial period has already been used for this workspace.',
        'payment_method' => 'Payment method',
        'pay_with' => 'Pay with :method',
        'total_due' => 'Amount due',
        'success' => 'Subscription activated. An invoice has been sent to you by email.',
        'trial_success' => 'Your free trial is activated. Welcome!',
        'most_popular' => 'Most popular',
        'activate_free' => 'Activate for free',
        'choose_plan' => 'Choose this plan',
        'from' => 'From',
        'billed_period' => 'billed for :months months',
        'free_forever' => 'No commitment',
    ],

    // Checkout page
    'checkout' => [
        'title' => 'Complete your subscription',
        'subtitle' => 'Review your order and choose your payment method.',
        'summary' => 'Summary',
        'plan' => 'Plan',
        'period' => 'Period',
        'total' => 'Total to pay',
        'choose_method' => 'Payment method',
        'pay_now' => 'Pay now',
        'pay_with_paypal' => 'Pay with PayPal',
        'secure_notice' => 'Secure payment. Your banking information never passes through our servers.',
        'back' => 'Back to plans',
    ],

    // Success page
    'success' => [
        'title' => 'Payment successful',
        'message' => 'Your subscription is activated. Thank you for your trust!',
        'invoice_sent' => 'An invoice (PDF) has just been sent to you by email.',
        'go_dashboard' => 'Go to dashboard',
        'valid_until' => 'Your subscription is valid until :date.',
    ],

    // Subscription invoice
    'invoice' => [
        'subject' => 'Your subscription invoice — :app',
        'greeting' => 'Hello :name,',
        'intro' => 'Thank you for your subscription. You will find your invoice attached (PDF).',
        'trial_intro' => 'Your trial period is activated. Here is the summary of your order attached.',
        'title' => 'Subscription invoice',
        'plan' => 'Plan',
        'period' => 'Period',
        'amount' => 'Amount',
        'date' => 'Date',
        'valid_until' => 'Valid until',
        'thanks' => 'Thank you for your trust.',
        'footer' => 'This is an automatically generated invoice.',
    ],

    // Middleware / access
    'access' => [
        'required' => 'An active subscription is required to access this page.',
        'expired' => 'Your subscription has expired. Please renew it to continue.',
    ],

    // Expiration reminder
    'reminder' => [
        'subject' => 'Your subscription expires soon — :app',
        'greeting' => 'Hello :name,',
        'body' => 'Your subscription expires on :date (in :days days). Remember to renew it to avoid any service interruption.',
        'cta' => 'Renew my subscription',
        'dashboard_alert' => 'Your subscription expires on :date (in :days days). Renew it to avoid any interruption.',
        'dashboard_expired' => 'Your subscription has expired. Renew it to regain full access.',
        'renew' => 'Renew',
    ],
];
