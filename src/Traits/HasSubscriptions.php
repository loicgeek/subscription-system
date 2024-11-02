<?php 

namespace NtechServices\SubscriptionSystem\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use NtechServices\SubscriptionSystem\Models\Plan;
use NtechServices\SubscriptionSystem\Models\PlanPrice;
use NtechServices\SubscriptionSystem\Models\Subscription;
use NtechServices\SubscriptionSystem\Enums\BillingCycle;
use NtechServices\SubscriptionSystem\Enums\SubscriptionStatus;
use NtechServices\SubscriptionSystem\Models\Coupon;

trait HasSubscriptions
{
    public function subscriptions() : MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function activeSubscription()
    {
        return $this->subscriptions()
            ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::TRIALING->value])
            ->first();
    }
    public function pendingSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', [SubscriptionStatus::PENDING->value])
            ->first();
    }

    public function subscribeToPlan(Plan $plan, BillingCycle $billingCycle, string $couponCode = null)
    {
        // Retrieve the price for the selected plan and billing cycle
        $price = PlanPrice::where('plan_id', $plan->id)
                          ->where('billing_cycle', $billingCycle->value)
                          ->first();

        if (!$price) {
            throw new \Exception("Price not found for this billing cycle.");
        }

        // Read grace period configuration
        $graceValue = config('subscription.default.grace_value'); // Grace value as a numerical value
        $graceCycle = config('subscription.default.grace_cycle'); // Grace cycle

        // Handle coupon application
        $discountAmount = $this->applyCoupon($couponCode, $price, $couponId);

        // Create the new subscription
        $subscription = $this->subscriptions()->updateOrCreate([
            "subscribable_id" => $this->id,
            "subscribable_type" => get_class($this),
        ],[
            'plan_id' => $plan->id,
            'plan_price_id' => $price->id,
            'start_date' => Carbon::now(),
            'next_billing_date' => $this->calculateNextBillingDate($billingCycle),
            'amount_due' => max(0, $price->price - $discountAmount), // Ensure amount due is not negative
            'currency' => $price->currency,
            'status' => SubscriptionStatus::PENDING->value,
            'grace_value' => $graceValue, // Read grace value from config
            'grace_cycle' => $graceCycle, // Read grace cycle from config
            'coupon_id' => $couponId, // Save the coupon ID used for this subscription
        ]);



        return $subscription;
    }

    function startSubscription()
    {
        $pendingSubscription = $this->pendingSubscription();
        if ($pendingSubscription) {
            $planPrice = $pendingSubscription->planPrice;
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

            $pendingSubscription->trial_ends_at = $trialEndsAt; 
            $pendingSubscription->next_billing_date  = $this->calculateNextBillingDate(BillingCycle::from($planPrice->billing_cycle));
            $pendingSubscription->status = SubscriptionStatus::ACTIVE->value;
            $pendingSubscription->save();
        }
        
    }
    

    protected function applyCoupon(?string $couponCode, PlanPrice $price, ?int &$couponId): float
    {
        $couponId = null; // Initialize coupon ID variable
        if (!$couponCode) {
            return 0; // No coupon applied
        }

        $coupon = Coupon::where('code', $couponCode)
                        ->where(function ($query) {
                            $query->whereNull('expires_at')
                                  ->orWhere('expires_at', '>', Carbon::now());
                        })
                        ->first();

        if ($coupon) {
            $couponId = $coupon->id; // Set the coupon ID
            if ($coupon->discount_type === 'percentage') {
                return $price->price * ($coupon->discount_amount / 100);
            }

            return min($coupon->discount_amount, $price->price); // Fixed amount, can't exceed the price
        }

        return 0; // Invalid coupon
    }

    public function updateSubscription(Plan $newPlan, BillingCycle $billingCycle)
    {
        $subscription = $this->activeSubscription();

        if ($subscription) {
            // Retrieve the new price
            $newPrice = PlanPrice::where('plan_id', $newPlan->id)
                                 ->where('billing_cycle', $billingCycle->value)
                                 ->first();

            if (!$newPrice) {
                throw new \Exception("New price not found for this billing cycle.");
            }

            // Calculate the prorated amount only if enabled in config
            $proratedAmount = config('subscription.default.enable_prorated_billing')
                ? $this->calculateProratedAmount($subscription)
                : 0; // Set to 0 if prorated billing is disabled

            // Update the subscription with the new plan and prorated amount
            $subscription->update([
                'plan_id' => $newPlan->id,
                'plan_price_id' => $newPrice->id,
                'amount_due' => $newPrice->price - $proratedAmount,
                'next_billing_date' => $this->calculateNextBillingDate($billingCycle),
                'prorated_amount' => $proratedAmount,
            ]);
        }
    }

    protected function calculateProratedAmount(Subscription $subscription): float
    {
        // Get the remaining time in the current billing cycle
        $remainingDays = Carbon::now()->diffInDays($subscription->next_billing_date);

        // Calculate the daily rate based on the current plan price
        $dailyRate = $subscription->amount_due / $this->daysInBillingCycle($subscription->plan_price_id);

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
        $subscription = $this->activeSubscription();

        if ($subscription) {
            if ($softCancel) {
                $subscription->update(['status' => SubscriptionStatus::CANCELED->value]);
            } else {
                $subscription->delete(); // Hard delete if not soft canceling
            }

           
        }
    }

    public function sendBillingReminder()
    {
        $subscription = $this->activeSubscription();

        if ($subscription && $subscription->next_billing_date->isToday()) {
           
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
        $subscription = $this->activeSubscription();

        if ($subscription) {
            // Calculate grace period based on grace value and cycle
            $graceValue = $subscription->grace_value; // Read from subscription
            $graceCycle = $subscription->grace_cycle; // Read from subscription
            
            // Determine how many days to add based on grace cycle
            $daysToAdd = match ($graceCycle) {
                'daily' => $graceValue,
                'weekly' => $graceValue * 7,
                'monthly' => $graceValue * 30,
                'quarterly' => $graceValue * 90,
                'yearly' => $graceValue * 365,
                default => 0,
            };

            $endOfGracePeriod = Carbon::parse($subscription->next_billing_date)->addDays($daysToAdd);
            return Carbon::now()->isBefore($endOfGracePeriod);
        }

        return false;
    }
}
