<?php 

return [
    'default' => [
        'trial_days' => 30,              // Default trial duration    
        'enable_prorated_billing' => false,
        'grace_value' => 5, // Example grace value
        'grace_cycle' => 'daily', // Example grace cycle
    ],
    'tables' => [
        'plans' => env('SUBSCRIPTION_PLAN_TABLE', 'plans'),
        'subscriptions' => env('SUBSCRIPTION_SUBSCRIPTION_TABLE', 'subscriptions'),
        'plan_prices' => env('SUBSCRIPTION_PLAN_PRICE_TABLE', 'plan_prices'),
        'plan_feature' => env('SUBSCRIPTION_PLAN_FEATURE_TABLE', 'plan_feature'),
        'features' => env('SUBSCRIPTION_FEATURES_TABLE', 'features'),
        'coupons' => env('SUBSCRIPTION_COUPON_TABLE', 'coupons'),
    ],
];
