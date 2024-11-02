<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use NtechServices\SubscriptionSystem\Enums\SubscriptionStatus;

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
        'coupon_id', // Add coupon_id to the fillable array
    ];
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('subscription.tables.subscriptions');
    }
    protected static function boot()
    {
        parent::boot();

        static::created(function ($subscription) {
            // Record history when a subscription is created
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
    

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPrice()
    {
        return $this->belongsTo(PlanPrice::class);
    }
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
    public function history()
    {
        return $this->hasMany(SubscriptionHistory::class);
    }



    public function isInTrialPeriod(): bool
    {
        return $this->status === SubscriptionStatus::TRIALING->value && Carbon::now()->isBefore($this->trial_ends_at);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE->value && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->next_billing_date);
    }

    public function renew()
    {
        if ($this->isExpired()) {
            // Logic to renew the subscription
            $this->next_billing_date = $this->calculateNextBillingDate();
            $this->amount_due = $this->calculateAmountDue();
            $this->status = SubscriptionStatus::ACTIVE->value; // Set status to active upon renewal
            $this->save();
        }
    }


    public function enterGracePeriod()
    {
        // Logic to enter grace period
        $daysToAdd = $this->calculateGracePeriodDays();
        $this->next_billing_date = Carbon::now()->addDays($daysToAdd);
        $this->save();
    }

    protected function calculateGracePeriodDays(): int
    {
        // Determine how many days to add based on grace value and cycle
        return match ($this->grace_cycle) {
            'daily' => $this->grace_value,
            'weekly' => $this->grace_value * 7,
            'monthly' => $this->grace_value * 30,
            'quarterly' => $this->grace_value * 90,
            'yearly' => $this->grace_value * 365,
            default => 0,
        };
    }

   

    // Method to record a history entry
    protected function recordHistory(string $status, ?string $details = null)
    {
        $this->history()->create([
            'plan_id' => $this->plan_id, // Save the plan ID in the history
            'status' => $status,
            'details' => $details,
        ]);
    }
}
