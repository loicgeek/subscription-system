<?php 

namespace NtechServices\SubscriptionSystem\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use NtechServices\SubscriptionSystem\Config\ConfigHelper;
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
        return $this->morphMany(ConfigHelper::getConfigClass('subscriptions',Subscription::class), 'subscribable');
    }

    public function activeSubscriptions():Collection
    {
        return $this->subscriptions()
            ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::TRIALING->value])
            ->get();
    }
    public function pendingSubscriptions(): Collection
    {
        return $this->subscriptions()
            ->whereIn('status', [SubscriptionStatus::PENDING->value])
            ->get();
    }
    public function subscribeToPlan(PlanPrice $planPrice, string $couponCode = null)
    {
        // Read grace period configuration
        $graceValue = ConfigHelper::get('default.grace_value'); // Grace value as a numerical value
        $graceCycle = ConfigHelper::get('default.grace_cycle'); // Grace cycle

        // Handle coupon application
        $discountAmount = $this->applyCoupon($couponCode, $planPrice, $couponId);

        // Create the new subscription
        $subscription = $this->subscriptions()->updateOrCreate([
            "subscribable_id" => $this->getKey(),
            "subscribable_type" => get_class($this),
        ],[
            'plan_id' => $planPrice->plan->id,
            'plan_price_id' => $planPrice->id,
            'start_date' => Carbon::now(),
            'next_billing_date' => $this->calculateNextBillingDate(BillingCycle::from($planPrice->billing_cycle)),
            'amount_due' => max(0, $planPrice->price - $discountAmount), // Ensure amount due is not negative
            'currency' => $planPrice->currency,
            'status' => SubscriptionStatus::PENDING->value,
            'grace_value' => $graceValue, // Read grace value from config
            'grace_cycle' => $graceCycle, // Read grace cycle from config
            'coupon_id' => $couponId, // Save the coupon ID used for this subscription
        ]);



        return $subscription;
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
}
