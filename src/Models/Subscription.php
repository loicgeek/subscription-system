<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;
use NtechServices\SubscriptionSystem\Enums\BillingCycle;
use NtechServices\SubscriptionSystem\Enums\SubscriptionStatus;

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
        'coupon_id',
    ];

    /**
     * @var string $table The table associated with the model.
     */
    protected $table;

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
        return $this->hasMany(ConfigHelper::getConfigClass('subscription_history', SubscriptionHistory::class) );
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
        return $this->status === SubscriptionStatus::ACTIVE->value && !$this->isExpired();
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
        }
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
            }
        });

        static::deleting(function ($subscription) {
            $subscription->recordHistory($subscription->status, 'Subscription deleted.');
        });
    }




    function startBilling()
    {
       
        $planPrice = $this->planPrice;
            // Read trial value and cycle from the plan
        $trialValue = $planPrice->trial_value; // Number of trial days or units
        $trialCycle = $planPrice->trial_cycle; // Trial cycle

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
        $this->next_billing_date  = $this->calculateNextBillingDate(BillingCycle::from($planPrice->billing_cycle));
        $this->status = SubscriptionStatus::ACTIVE->value;
        $this->save();
        
    }
    

    public function updateSubscription(PlanPrice $newPrice)
    {
        // Calculate the prorated amount only if enabled in config
        $proratedAmount = ConfigHelper::get('default.enable_prorated_billing')
        ? $this->calculateProratedAmount($this)
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

    protected function calculateProratedAmount(): float
    {
        // Get the remaining time in the current billing cycle
        $remainingDays = Carbon::now()->diffInDays($this->next_billing_date);

        // Calculate the daily rate based on the current plan price
        $dailyRate = $this->amount_due / $this->daysInBillingCycle($this->plan_price_id);

        // Calculate the prorated amount
        return $dailyRate * $remainingDays;
    }

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

    public function cancelSubscription(bool $softCancel = true)
    {
        if ($softCancel) {
            $this->update(['status' => SubscriptionStatus::CANCELED->value]);
        } else {
            $this->delete(); // Hard delete if not soft canceling
        }
    }

    public function sendBillingReminder()
    {

        if ($this->next_billing_date->isToday()) {
           
        }
    }

    protected function calculateNextBillingDate(BillingCycle $billingCycle)
    {
        return match ($billingCycle) {
            BillingCycle::DAILY => Carbon::now()->addDay(),
            BillingCycle::WEEKLY => Carbon::now()->addWeek(),
            BillingCycle::MONTHLY => Carbon::now()->addMonth(),
            BillingCycle::QUARTERLY => Carbon::now()->addMonths(3),
            BillingCycle::YEARLY => Carbon::now()->addYear(),
        };
    }

    public function isSubscriptionInGracePeriod(): bool
    {

        // Calculate grace period based on grace value and cycle
        $graceValue = $this->grace_value; // Read from subscription
        $graceCycle = $this->grace_cycle; // Read from subscription
        
        // Determine how many days to add based on grace cycle
        $daysToAdd = match ($graceCycle) {
            'daily' => $graceValue,
            'weekly' => $graceValue * 7,
            'monthly' => $graceValue * 30,
            'quarterly' => $graceValue * 90,
            'yearly' => $graceValue * 365,
            default => 0,
        };

        $endOfGracePeriod = Carbon::parse($this->next_billing_date)->addDays($daysToAdd);
        return Carbon::now()->isBefore($endOfGracePeriod);
    }
}
