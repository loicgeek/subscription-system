<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;
use NtechServices\SubscriptionSystem\Enums\BillingCycle;
use NtechServices\SubscriptionSystem\Enums\SubscriptionStatus;
use NtechServices\SubscriptionSystem\Services\FeatureLimitationService;

/**
 * Class Subscription
 *
 * Represents a subscription associated with a plan, plan price, and coupon.
 *
 * @property int $id
 * @property int $plan_id
 * @property int $plan_price_id
 * @property int $subscribable_id
 * @property string $subscribable_type
 * @property Carbon $start_date
 * @property Carbon|null $trial_ends_at
 * @property Carbon $next_billing_date
 * @property float $amount_due
 * @property string $currency
 * @property string $status
 * @property int|null $grace_value
 * @property string|null $grace_cycle
 * @property float|null $prorated_amount
 * @property int|null $coupon_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder active() Scope for active subscriptions
 */
class Subscription extends Model
{
    
    protected $fillable = [
        'plan_id',
        'plan_price_id',
        'subscribable_id',
        'subscribable_type',
        'start_date',
        'trial_ends_at',
        'next_billing_date',
        'amount_due',
        'currency',
        'status',
        'grace_value',
        'grace_cycle',
        'prorated_amount',
        'coupon_id',
    ];

    /**
     * @var string $table The table associated with the model.
     */
    protected $table;

    /**
     * Get the subscriber (the user who created the subscription).
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo('subscribable');
    }

    /**
     * Subscription constructor.
     *
     * Sets the table name dynamically from configuration.
     *
     * @param array $attributes Initial attributes for the model instance
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('subscriptions','subscriptions');
    }

    /**
     * The plan associated with the subscription.
     *
     * @return BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('plan', Plan::class));
    }

    /**
     * The plan price associated with the subscription.
     *
     * @return BelongsTo
     */
    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('plan_price', PlanPrice::class));
    }

    /**
     * The coupon associated with the subscription.
     *
     * @return BelongsTo
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('coupon', Coupon::class));
    }

    /**
     * The history records associated with the subscription.
     *
     * @return HasMany
     */
    public function history(): HasMany
    {
        return $this->hasMany(ConfigHelper::getConfigClass('subscription_history', SubscriptionHistory::class));
    }

    /**
     * Get features associated with the subscription's plan.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function features()
    {
        $planFeatureClass = ConfigHelper::getConfigClass('plan_feature', PlanFeature::class);
        $featureClass = ConfigHelper::getConfigClass('feature', Feature::class);
        
        return $this->hasManyThrough(
            $featureClass,
            $planFeatureClass,
            'plan_id', // Foreign key on plan_feature table
            'id', // Foreign key on features table
            'plan_id', // Local key on subscriptions table
            'feature_id' // Local key on plan_feature table
        );
    }

    /**
     * Checks if the subscription is within the trial period.
     *
     * @return bool
     */
    public function isInTrialPeriod(): bool
    {
        return $this->status === SubscriptionStatus::TRIALING->value && Carbon::now()->isBefore($this->trial_ends_at);
    }

    /**
     * Checks if the subscription is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        // Check if status is active
        if ($this->status !== SubscriptionStatus::ACTIVE->value) {
            return false;
        }
        
        // Check if in trial period
        if ($this->trial_ends_at && Carbon::now()->isBefore($this->trial_ends_at)) {
            return true;
        }
        
        // Check if subscription has expired
        if ($this->isExpired()) {
            // Check if in grace period
            if ($this->isSubscriptionInGracePeriod()) {
                return true;
            }
            return false;
        }
        
        return true;
    }

    /**
     * Checks if the subscription has expired based on the billing date.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->next_billing_date);
    }

    /**
     * Renews the subscription by updating the billing date and amount due.
     *
     * @return void
     */
    public function renew(): void
    {
        if ($this->isExpired()) {
            $planPrice = $this->planPrice;
            $this->next_billing_date = $this->calculateNextBillingDate(BillingCycle::from($planPrice->billing_cycle));
            $this->amount_due = $this->calculateAmountDue();
            $this->status = SubscriptionStatus::ACTIVE->value;
            $this->save();
            
            // Record renewal in history
            $this->recordHistory($this->status, 'Subscription renewed.');
        }
    }

    /**
     * Calculate amount due, taking into account any coupon discounts
     * 
     * @return float
     */
    protected function calculateAmountDue(): float
    {
        $price = $this->planPrice->price;
        
        // Apply coupon discount if present
        if ($this->coupon_id) {
            $coupon = $this->coupon;
            
            // Check if coupon is still valid
            if (!$coupon->expires_at || Carbon::now()->isBefore($coupon->expires_at)) {
                if ($coupon->discount_type === 'percentage') {
                    $price = $price * (1 - ($coupon->discount_amount / 100));
                } else { // fixed amount
                    $price = max(0, $price - $coupon->discount_amount);
                }
            }
        }
        
        return $price;
    }

    /**
     * Enters a grace period by extending the next billing date.
     *
     * @return void
     */
    public function enterGracePeriod(): void
    {
        $daysToAdd = $this->calculateGracePeriodDays();
        $this->next_billing_date = Carbon::now()->addDays($daysToAdd);
        $this->save();
        
        // Record grace period in history
        $this->recordHistory($this->status, "Entered grace period for {$daysToAdd} days.");
    }

    /**
     * Calculates the number of days in the grace period based on grace value and cycle.
     *
     * @return int
     */
    protected function calculateGracePeriodDays(): int
    {
        return match ($this->grace_cycle) {
            'daily' => $this->grace_value,
            'weekly' => $this->grace_value * 7,
            'monthly' => $this->grace_value * 30,
            'quarterly' => $this->grace_value * 90,
            'yearly' => $this->grace_value * 365,
            default => 0,
        };
    }

    /**
     * Records a history entry for changes to the subscription.
     *
     * @param string $status The status of the subscription
     * @param string|null $details Additional details about the change
     * @return void
     */
    protected function recordHistory(string $status, ?string $details = null): void
    {
        $this->history()->create([
            'plan_id' => $this->plan_id,
            'status' => $status,
            'details' => $details,
        ]);
    }

    /**
     * Boot the model and register model events for recording history.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($subscription) {
            $subscription->recordHistory($subscription->status, 'Subscription created.');
        });

        static::updating(function ($subscription) {
            if ($subscription->isDirty('status')) {
                $originalStatus = $subscription->getOriginal('status');
                $newStatus = $subscription->status;
                $subscription->recordHistory($newStatus, "Status changed from {$originalStatus} to {$newStatus}.");
            } elseif ($subscription->isDirty('plan_id')) {
                $originalPlanId = $subscription->getOriginal('plan_id');
                $newPlanId = $subscription->plan_id;
                $subscription->recordHistory($subscription->status, "Plan changed from ID {$originalPlanId} to ID {$newPlanId}.");
            } elseif ($subscription->isDirty('plan_price_id')) {
                $originalPlanPriceId = $subscription->getOriginal('plan_price_id');
                $newPlanPriceId = $subscription->plan_price_id;
                $subscription->recordHistory($subscription->status, "Plan price changed from ID {$originalPlanPriceId} to ID {$newPlanPriceId}.");
            }
        });

        static::deleting(function ($subscription) {
            $subscription->recordHistory($subscription->status, 'Subscription deleted.');
        });
    }

    /**
     * Start billing for the subscription
     * 
     * @return void
     */
    public function startBilling(): void
    {
        $planPrice = $this->planPrice;
        // Read trial value and cycle from the plan
        $trialValue = $this->plan->trial_value; // Number of trial days or units
        $trialCycle = $this->plan->trial_cycle; // Trial cycle

        // Handle trial period
        $trialEndsAt = null;
        if ($trialValue > 0) {
            switch ($trialCycle) {
                case 'daily':
                    $trialEndsAt = Carbon::now()->addDays($trialValue);
                    break;
                case 'weekly':
                    $trialEndsAt = Carbon::now()->addWeeks($trialValue);
                    break;
                case 'monthly':
                    $trialEndsAt = Carbon::now()->addMonths($trialValue);
                    break;
                case 'quarterly':
                    $trialEndsAt = Carbon::now()->addMonths(3 * $trialValue);
                    break;
                case 'yearly':
                    $trialEndsAt = Carbon::now()->addYears($trialValue);
                    break;
            }
        }

        $this->trial_ends_at = $trialEndsAt; 
        $this->next_billing_date = $this->calculateNextBillingDate(BillingCycle::from($planPrice->billing_cycle));
        $this->status = $trialEndsAt ? SubscriptionStatus::TRIALING->value : SubscriptionStatus::ACTIVE->value;
        $this->save();
    }
    
    /**
     * Update subscription to a new plan/price
     * 
     * @param PlanPrice $newPrice
     * @return void
     */
    public function updateSubscription(PlanPrice $newPrice): void
    {
        // Calculate the prorated amount only if enabled in config
        $proratedAmount = ConfigHelper::get('default.enable_prorated_billing')
            ? $this->calculateProratedAmount()
            : 0; // Set to 0 if prorated billing is disabled

        // Update the subscription with the new plan and prorated amount
        $this->update([
            'plan_id' => $newPrice->plan->id,
            'plan_price_id' => $newPrice->id,
            'amount_due' => $newPrice->price - $proratedAmount,
            'next_billing_date' => $this->calculateNextBillingDate(BillingCycle::from($newPrice->billing_cycle)),
            'prorated_amount' => $proratedAmount,
        ]);
    }

    /**
     * Calculate prorated amount for plan changes
     * 
     * @return float
     */
    protected function calculateProratedAmount(): float
    {
        // Get the remaining time in the current billing cycle
        $remainingDays = Carbon::now()->diffInDays($this->next_billing_date);

        // Calculate the daily rate based on the current plan price
        $dailyRate = $this->amount_due / $this->daysInBillingCycle($this->plan_price_id);

        // Calculate the prorated amount
        return $dailyRate * $remainingDays;
    }

    /**
     * Get number of days in billing cycle
     * 
     * @param int $planPriceId
     * @return int
     */
    protected function daysInBillingCycle(int $planPriceId): int
    {
        // Assuming monthly billing cycles have 30 days
        $price = PlanPrice::find($planPriceId);
        return match ($price->billing_cycle) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            default => 30,
        };
    }

    /**
     * Cancel a subscription
     * 
     * @param bool $softCancel Whether to soft cancel or hard delete
     * @return void
     */
    public function cancelSubscription(bool $softCancel = true): void
    {
        if ($softCancel) {
            $this->update(['status' => SubscriptionStatus::CANCELED->value]);
            $this->recordHistory(SubscriptionStatus::CANCELED->value, 'Subscription was canceled.');
        } else {
            // Record history before deleting
            $this->recordHistory(SubscriptionStatus::CANCELED->value, 'Subscription was hard deleted.');
            $this->delete(); // Hard delete if not soft canceling
        }
    }

    /**
     * Send billing reminder
     * 
     * @return void
     */
    public function sendBillingReminder(): void
    {
        if ($this->next_billing_date->isToday()) {
            // Implementation for sending reminders via email or notifications
            // This would typically integrate with your notification system
        }
    }

    /**
     * Calculate next billing date based on billing cycle
     * 
     * @param BillingCycle $billingCycle
     * @return Carbon
     */
    protected function calculateNextBillingDate(BillingCycle $billingCycle): Carbon
    {
        return match ($billingCycle) {
            BillingCycle::DAILY => Carbon::now()->addDay(),
            BillingCycle::WEEKLY => Carbon::now()->addWeek(),
            BillingCycle::MONTHLY => Carbon::now()->addMonth(),
            BillingCycle::QUARTERLY => Carbon::now()->addMonths(3),
            BillingCycle::YEARLY => Carbon::now()->addYear(),
        };
    }

    /**
     * Check if subscription is in grace period
     * 
     * @return bool
     */
    public function isSubscriptionInGracePeriod(): bool
    {
        // If no grace period is defined, return false
        if (!$this->grace_value || !$this->grace_cycle) {
            return false;
        }

        // Determine how many days to add based on grace cycle
        $daysToAdd = match ($this->grace_cycle) {
            'daily' => $this->grace_value,
            'weekly' => $this->grace_value * 7,
            'monthly' => $this->grace_value * 30,
            'quarterly' => $this->grace_value * 90,
            'yearly' => $this->grace_value * 365,
            default => 0,
        };

        $endOfGracePeriod = Carbon::parse($this->next_billing_date)->addDays($daysToAdd);
        return Carbon::now()->isBefore($endOfGracePeriod);
    }

    /**
     * Check if the subscription has access to a specific feature
     * 
     * @param string $featureName
     * @return bool
     */
    public function hasFeature(string $featureName): bool
    {
        // If subscription is not active, no features are available
        if (!$this->isActive()) {
            return false;
        }
        
        return app(FeatureLimitationService::class)->hasFeature($this, $featureName);
    }

    /**
     * Get the value of a specific feature for this subscription
     * 
     * @param string $featureName
     * @return string|null
     */
    public function getFeatureValue(string $featureName): ?string
    {
        // If subscription is not active, no features are available
        if (!$this->isActive()) {
            return null;
        }
        
        return app(FeatureLimitationService::class)->getFeatureValue($this, $featureName);
    }

    /**
     * Check if the subscription has a feature with a specific value
     * 
     * @param string $featureName
     * @param mixed $requiredValue
     * @return bool
     */
    public function hasFeatureWithValue(string $featureName, $requiredValue): bool
    {
        // If subscription is not active, no features are available
        if (!$this->isActive()) {
            return false;
        }
        
        return app(FeatureLimitationService::class)->hasFeatureWithValue($this, $featureName, $requiredValue);
    }

    /**
     * Check if the subscription has reached the limit for a specific feature
     * 
     * @param string $featureName
     * @param int $currentUsage
     * @return bool
     */
    public function hasReachedLimit(string $featureName, int $currentUsage): bool
    {
        // If subscription is not active, all limits are reached
        if (!$this->isActive()) {
            return true;
        }
        
        return app(FeatureLimitationService::class)->hasReachedLimit($this, $featureName, $currentUsage);
    }

    /**
     * Get all available features for this subscription
     * 
     * @return array
     */
    public function getAvailableFeatures(): array
    {
        // If subscription is not active, no features are available
        if (!$this->isActive()) {
            return [];
        }
        
        return app(FeatureLimitationService::class)->getAvailableFeatures($this);
    }

    /**
     * Scope a query to only include active subscriptions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE->value)
            ->where(function ($query) {
                $query->where('next_billing_date', '>=', Carbon::now())
                    ->orWhere(function ($query) {
                        $query->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '>=', Carbon::now());
                    });
            });
    }

    /**
     * Scope a query to only include expired subscriptions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('next_billing_date', '<', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '<', Carbon::now());
            });
    }

    /**
     * Scope a query to only include trialing subscriptions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrialing($query)
    {
        return $query->where('status', SubscriptionStatus::TRIALING->value)
            ->where('trial_ends_at', '>=', Carbon::now());
    }
}