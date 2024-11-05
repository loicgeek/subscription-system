<?php 

return [
    'default' => [
        'enable_prorated_billing' => false,
        'grace_value' => 5, // Example grace value
        'grace_cycle' => 'daily', // Example grace cycle
    ],
    'tables' => [
        "prefix" => "ntech_",
        'plans' =>'plans',
        "subscription_histories" => 'subscription_histories',
        'subscriptions' => 'subscriptions',
        'plan_prices' => 'plan_prices',
        'plan_feature' => 'plan_feature',
        'features' => 'features',
        'coupons' => 'coupons',
    ],
    'models'=>[
        'feature' => NtechServices\SubscriptionSystem\Models\Feature::class,
        'plan' => NtechServices\SubscriptionSystem\Models\Plan::class,
        'subscription' => NtechServices\SubscriptionSystem\Models\Subscription::class,
        'plan_price' => NtechServices\SubscriptionSystem\Models\PlanPrice::class,
        'coupon' => NtechServices\SubscriptionSystem\Models\Coupon::class,
        'subscription_history' => NtechServices\SubscriptionSystem\Models\SubscriptionHistory::class
    ],

];
