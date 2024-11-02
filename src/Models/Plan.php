<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\Factory;
use NtechServices\SubscriptionSystem\Database\Factories\PlanFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug'
    ];

    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('subscription.tables.plans');
    }

    use HasFactory;
    protected static function newFactory(): Factory
    {
        return PlanFactory::new();
    }

    /**
     * Get the prices for the plan.
     */
    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

    /**
     * The features that belong to the plan.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class)
                    ->withPivot('value')
                    ->withTimestamps();
    }
}
