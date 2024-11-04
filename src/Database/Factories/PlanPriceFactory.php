<?php 
 namespace NtechServices\SubscriptionSystem\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NtechServices\SubscriptionSystem\Config\ConfigHelper;
use NtechServices\SubscriptionSystem\Models\Plan;
use NtechServices\SubscriptionSystem\Models\PlanPrice;

class PlanPriceFactory extends Factory
{
    protected $model = PlanPrice::class;

    public function definition()
    {
        return [
            'plan_id' => ConfigHelper::getConfigClass('plan', Plan::class)::factory(), // Create a new Plan if necessary
            'billing_cycle' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly', 'yearly']),
            'price' => $this->faker->randomFloat(2, 5, 100), // Random price between 5 and 100
            'currency' => $this->faker->currencyCode, // Random currency code
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}