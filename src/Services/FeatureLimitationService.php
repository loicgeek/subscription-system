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
     * @param int $currentUsage
     * @return bool
     */


    public function hasReachedLimit(Subscription $subscription, string $featureName): bool
    {
        $featureValue = $this->getFeatureValue($subscription, $featureName);
        if ($featureValue === null) return true;
        if ($featureValue === 'unlimited' || $featureValue === '-1' || $featureValue === -1) return false;

        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->first();

        $used = ConfigHelper::getConfigClass("subscription_feature_usage",SubscriptionFeatureUsage::class)
            ::where('subscription_id', $subscription->id)
            ->where('feature_id', $feature->id)
            ->value('used') ?? 0;

        return (int)$used >= (int)$featureValue;
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

    public function incrementUsage(Subscription $subscription, string $featureName, int $amount = 1): void
    {
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->firstOrFail();

        $usage = ConfigHelper::getConfigClass('subscription_feature_usage', SubscriptionFeatureUsage::class)::firstOrCreate([
            'subscription_id' => $subscription->id,
            'feature_id' => $feature->id,
        ],
        [
            'used' => 0,
            'reset_at' => $subscription->next_billing_date, // Default reset cycle
        ]);

        $usage->increment('used', $amount);
    }
    
    public function getFeatureUsage(Subscription $subscription, string $featureName): int
    {
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->firstOrFail();

        $usage = ConfigHelper::getConfigClass('subscription_feature_usage', SubscriptionFeatureUsage::class)::firstOrCreate([
            'subscription_id' => $subscription->id,
            'feature_id' => $feature->id,
        ]);
        if($usage === null){
            return 0;
        }

        return $usage->used;
    }
    
    
}