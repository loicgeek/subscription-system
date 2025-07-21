<?php

namespace NtechServices\SubscriptionSystem\Exceptions;

use Exception;
use NtechServices\SubscriptionSystem\Models\Subscription;

class FeatureNotAvailableException extends Exception
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly string $featureName
    ) {
        $message = "Feature '{$featureName}' is not available in subscription plan '{$subscription->plan->name}'";
        parent::__construct($message, 403); // HTTP 403 Forbidden
    }

    public function render($request){
        return response()->json([
            "message" => $this->message,
            "feature_name"=>$this->featureName,
            "plan_name" => $this->subscription->plan->name,
        ],403);
    }
}