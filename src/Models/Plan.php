<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\Factory;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;
use NtechServices\SubscriptionSystem\Database\Factories\PlanFactory;

/**
 * Class Plan
 *
 * Represents a subscription plan with details such as name, description, slug,
 * associated prices, and features.
 *
 * @property int $id
 * @property string $name The name of the plan
 * @property string $slug URL-friendly unique identifier for the plan
 * @property string|null $description Description of the plan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \NtechServices\SubscriptionSystem\Database\Factories\PlanFactory factory(...$parameters)
 */
class Plan extends Model
{
    use HasFactory;

    /**
     * @var array<int, string> $fillable The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'slug'
    ];

    /**
     * @var string $table The table associated with the model.
     */
    protected $table;

    /**
     * Plan constructor.
     *
     * Sets the table name dynamically from configuration.
     *
     * @param array $attributes Initial attributes for the model instance
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('plans','plans');
    }

    /**
     * Define the factory for the Plan model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory(): Factory
    {
        return PlanFactory::new();
    }

    /**
     * Get the prices associated with the plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function planPrices(): HasMany
    {
        return $this->hasMany(ConfigHelper::getConfigClass('plan_price', PlanPrice::class));
    }

    /**
     * The features associated with the plan.
     *
     * Each feature can have a pivot value indicating its configuration for this plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(ConfigHelper::getConfigClass('feature', Feature::class),
        ConfigHelper::getConfigTable('plan_feature'))
                    ->withPivot('value')
                    ->withTimestamps();
    }
}
