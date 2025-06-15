<?php

namespace NtechServices\SubscriptionSystem\Exceptions;

use Exception;
use NtechServices\SubscriptionSystem\Models\Subscription;
use NtechServices\SubscriptionSystem\Models\Feature;

class FeatureUsageLimitExceededException extends Exception
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly Feature $feature,
        public readonly int $currentUsage,
        public readonly int $limit,
        public readonly int $attemptedUsage = 1
    ) {
        $message = "Feature usage limit exceeded for '{$feature->name}'. " .
                  "Current usage: {$currentUsage}, Limit: {$limit}, " .
                  "Attempted to use: {$attemptedUsage}";
        
        parent::__construct($message, 429); // HTTP 429 Too Many Requests
    }

    public function getFeatureId(): int
    {
        return $this->feature->id;
    }

    public function getSubscriptionId(): int
    {
        return $this->subscription->id;
    }

    public function getRemainingUsage(): int
    {
        return max(0, $this->limit - $this->currentUsage);
    }

    public function getExcessUsage(): int
    {
        return max(0, ($this->currentUsage + $this->attemptedUsage) - $this->limit);
    }
}
