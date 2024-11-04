<?php 
 namespace NtechServices\SubscriptionSystem\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;
use NtechServices\SubscriptionSystem\Models\Plan;

class PlanFactory extends Factory
{
    protected $model;

    public function __construct()
    {
        $this->model = ConfigHelper::getConfigClass('plan', Plan::class);
    }

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentence,
            'trial_value' => $this->faker->numberBetween(1, 30), // Number of trial days
            'trial_cycle' => $this->faker->randomElement(['daily', 'weekly', 'monthly']), // Trial cycle
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
