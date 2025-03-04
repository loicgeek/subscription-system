<?php

namespace NtechServices\SubscriptionSystem\Services;

use Carbon\Carbon;
use NtechServices\SubscriptionSystem\Models\Feature;
use NtechServices\SubscriptionSystem\Models\PlanFeature;
use NtechServices\SubscriptionSystem\Models\Subscription;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;

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
        // Get the feature by name
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        $feature = $featureClass::where('name', $featureName)->first();
        
        if (!$feature) {
            return false;
        }
        
        // Check if subscription is active
        if (!$subscription->isActive()) {
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
        if ($featureValue === 'unlimited') {
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
    public function hasReachedLimit(Subscription $subscription, string $featureName, int $currentUsage): bool
    {
        $featureValue = $this->getFeatureValue($subscription, $featureName);
        
        if ($featureValue === null) {
            return true; // If the feature doesn't exist, limit is reached
        }
        
        // If the feature is unlimited
        if ($featureValue === 'unlimited') {
            return false;
        }
        
        // For numeric features
        if (is_numeric($featureValue)) {
            return $currentUsage >= (int)$featureValue;
        }
        
        // For boolean features
        if (in_array(strtolower($featureValue), ['false', 'no', '0', 'off'])) {
            return true;
        }
        
        return false;
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
}