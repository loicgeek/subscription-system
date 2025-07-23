<?php

namespace NtechServices\SubscriptionSystem\Services;

use Carbon\Carbon;
use NtechServices\SubscriptionSystem\Models\Feature;
use NtechServices\SubscriptionSystem\Models\PlanFeature;
use NtechServices\SubscriptionSystem\Models\Subscription;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;
use NtechServices\SubscriptionSystem\Models\SubscriptionFeatureUsage;

class FeatureLimitationService
{
   


    /**
     * Check if the subscription has access to a specific feature
     *
     * @param Subscription $subscription
     * @param string $featureName
     * @return bool
     */
    public function hasFeature(Subscription $subscription, string $featureName): bool
    {
        // Check if subscription is active
        if (!$subscription->isActive()) {
            return false;
        }
        // Get the feature by name
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->first();
        
        if (!$feature) {
            return false;
        }
        
        // Check if the plan has this feature
        $planFeatureClass = ConfigHelper::getConfigClass('plan_feature', PlanFeature::class);
        $planFeature = $planFeatureClass::where('plan_id', $subscription->plan_id)
            ->where('feature_id', $feature->id)
            ->first();
            
        return $planFeature !== null;
    }
    
    /**
     * Get the value of a feature for a subscription
     *
     * @param Subscription $subscription
     * @param string $featureName
     * @return string|null
     */
    public function getFeatureValue(Subscription $subscription, string $featureName): ?string
    {
        // Get the feature by name
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->first();
        
        if (!$feature) {
            return null;
        }
        
        // Check if subscription is active
        if (!$subscription->isActive()) {
            return null;
        }
        
        // Get the feature value from plan_feature
        $planFeatureClass = ConfigHelper::getConfigClass('plan_feature', PlanFeature::class);
        $planFeature = $planFeatureClass::where('plan_id', $subscription->plan_id)
            ->where('feature_id', $feature->id)
            ->first();
            
        return $planFeature ? $planFeature->value : null;
    }
    
    /**
     * Check if a subscription has access to a feature with a specific value
     *
     * @param Subscription $subscription
     * @param string $featureName
     * @param mixed $requiredValue
     * @return bool
     */
    public function hasFeatureWithValue(Subscription $subscription, string $featureName, $requiredValue): bool
    {
        $featureValue = $this->getFeatureValue($subscription, $featureName);
        
        if ($featureValue === null) {
            return false;
        }
        
        // Handle different types of feature values
        if ($featureValue === 'unlimited' || $featureValue === '-1' || $featureValue === -1) {
            return true;
        }
        
        // For boolean features
        if (in_array(strtolower($featureValue), ['true', 'yes', '1', 'on'])) {
            return true;
        }
        
        // For numeric features
        if (is_numeric($featureValue) && is_numeric($requiredValue)) {
            return (float)$featureValue >= (float)$requiredValue;
        }
        
        // For string comparison
        return $featureValue == $requiredValue;
    }
    
    /**
     * Check if the user has reached the limit for a specific feature
     *
     * @param Subscription $subscription
     * @param string $featureName
     * @return bool
     */
    public function hasReachedLimit(Subscription $subscription, string $featureName): bool
    {
        $featureValue = $this->getFeatureValue($subscription, $featureName);
        if ($featureValue === null) return true;
        if ($featureValue === 'unlimited' || $featureValue === '-1' || $featureValue === -1) return false;

        $used = $this->getFeatureUsage($subscription, $featureName);
        return $used >= (int)$featureValue;
    }

    /**
     * Get a list of all available features for a subscription
     *
     * @param Subscription $subscription
     * @return array
     */
    public function getAvailableFeatures(Subscription $subscription): array
    {
        if (!$subscription->isActive()) {
            return [];
        }
        
        $planFeatureClass = ConfigHelper::getConfigClass('plan_feature', PlanFeature::class);
        $planFeatures = $planFeatureClass::where('plan_id', $subscription->plan_id)
            ->with('feature')
            ->get();
            
        $features = [];
        
        foreach ($planFeatures as $planFeature) {
            $features[] = [
                'name' => $planFeature->feature->name,
                'description' => $planFeature->feature->description,
                'value' => $planFeature->value
            ];
        }
        
        return $features;
    }

    /**
     * Increment feature usage for the current subscription period
     *
     * @param Subscription $subscription
     * @param string $featureName
     * @param int $amount
     * @return void
     */
    public function incrementUsage(Subscription $subscription, string $featureName, int $amount = 1): void
    {
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->firstOrFail();

        $usage = $this->getOrCreateCurrentPeriodUsage($subscription, $feature);
        $usage->increment('used', $amount);
    }
    
    /**
     * Get feature usage for the current subscription period
     *
     * @param Subscription $subscription
     * @param string $featureName
     * @return int
     */
    public function getFeatureUsage(Subscription $subscription, string $featureName): int
    {
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->first();
        
        if (!$feature) {
            return 0;
        }

        $usage = $this->getOrCreateCurrentPeriodUsage($subscription, $feature);
        return $usage->used ?? 0;
    }
    
    /**
     * Get or create usage record for the current subscription period
     *
     * @param Subscription $subscription
     * @param Feature $feature
     * @return SubscriptionFeatureUsage
     */
    private function getOrCreateCurrentPeriodUsage(Subscription $subscription, $feature)
    {
        $usageClass = ConfigHelper::getConfigClass('subscription_feature_usage', SubscriptionFeatureUsage::class);
        $currentPeriodStart = $this->getCurrentPeriodStart($subscription);
        $nextPeriodStart = $this->getNextPeriodStart($subscription);

        // Try to find existing usage for current period
        $usage = $usageClass::where('subscription_id', $subscription->id)
            ->where('feature_id', $feature->id)
            ->where('created_at', '>=', $currentPeriodStart)
            ->where('reset_at', $nextPeriodStart)
            ->first();

        // If no usage found or it's from a previous period, create new one
        if (!$usage) {
            $usage = $usageClass::create([
                'subscription_id' => $subscription->id,
                'feature_id' => $feature->id,
                'used' => 0,
                'reset_at' => $nextPeriodStart,
            ]);
        }

        return $usage;
    }
    
    /**
     * Get the start date of the current billing period
     *
     * @param Subscription $subscription
     * @return Carbon
     */
    private function getCurrentPeriodStart(Subscription $subscription): Carbon
    {
        $now = Carbon::now();
        $subscriptionStart = Carbon::parse($subscription->start_date ?? $subscription->created_at);
        
        // If subscription is in trial, use trial start (subscription start)
        if ($subscription->isInTrialPeriod()) {
            return $subscriptionStart;
        }
        
        // Calculate the current period start based on billing cycle
        return $this->calculatePeriodStart($subscription, $now);
    }
    
    /**
     * Get the start date of the next billing period
     *
     * @param Subscription $subscription
     * @return Carbon
     */
    private function getNextPeriodStart(Subscription $subscription): Carbon
    {
        // Use next_billing_date from subscription
        return Carbon::parse($subscription->next_billing_date);
    }
    
    /**
     * Calculate period start based on subscription billing cycle
     *
     * @param Subscription $subscription
     * @param Carbon $referenceDate
     * @return Carbon
     */
    private function calculatePeriodStart(Subscription $subscription, Carbon $referenceDate): Carbon
    {
        $subscriptionStart = Carbon::parse($subscription->start_date ?? $subscription->created_at);
        $nextBillingDate = Carbon::parse($subscription->next_billing_date);
        $billingCycle = $subscription->planPrice->billing_cycle;
        
        // Calculate how long one billing cycle is
        $cycleDays = $this->getBillingCycleDays($billingCycle);
        
        // Calculate current period start by subtracting one cycle from next billing date
        $currentPeriodStart = $nextBillingDate->copy()->subDays($cycleDays);
        
        // Ensure we don't go before subscription start
        if ($currentPeriodStart->isBefore($subscriptionStart)) {
            return $subscriptionStart;
        }
        
        return $currentPeriodStart;
    }
    
    /**
     * Get number of days in a billing cycle
     *
     * @param string $billingCycle
     * @return int
     */
    private function getBillingCycleDays(string $billingCycle): int
    {
        return match (strtolower($billingCycle)) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            default => 30, // Default to monthly
        };
    }
    
    /**
     * Reset usage for a specific feature (useful for manual resets)
     *
     * @param Subscription $subscription
     * @param string $featureName
     * @return void
     */
    public function resetFeatureUsage(Subscription $subscription, string $featureName): void
    {
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->first();
        
        if (!$feature) {
            return;
        }
        
        $usageClass = ConfigHelper::getConfigClass('subscription_feature_usage', SubscriptionFeatureUsage::class);
        $usage = $this->getOrCreateCurrentPeriodUsage($subscription, $feature);
        $usage->update(['used' => 0]);
    }
    
    /**
     * Get detailed usage information for a feature
     *
     * @param Subscription $subscription
     * @param string $featureName
     * @return array
     */
    public function getFeatureUsageDetails(Subscription $subscription, string $featureName): array
    {
        $limit = $this->getFeatureValue($subscription, $featureName);
        $used = $this->getFeatureUsage($subscription, $featureName);
        $isUnlimited = in_array($limit, ['unlimited', '-1', -1], true);
        
        return [
            'feature_name' => $featureName,
            'limit' => $isUnlimited ? 'unlimited' : (int)$limit,
            'used' => $used,
            'remaining' => $isUnlimited ? 'unlimited' : max(0, (int)$limit - $used),
            'percentage_used' => $isUnlimited ? 0 : (($limit > 0) ? round(($used / (int)$limit) * 100, 2) : 100),
            'is_unlimited' => $isUnlimited,
            'has_reached_limit' => $this->hasReachedLimit($subscription, $featureName),
            'current_period_start' => $this->getCurrentPeriodStart($subscription)->toDateTimeString(),
            'next_reset_date' => $this->getNextPeriodStart($subscription)->toDateTimeString(),
        ];
    }
    
    
}