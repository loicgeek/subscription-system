<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;



/**
 * Class PlanFeature
 *
 * Represents the pricing details for a specific subscription plan, including the price, currency, and billing cycle.
 *
 * @property int $id
 * @property int $plan_id
 * @property int $feature_id
 * @property string $value
 * @property bool $is_soft_limit
 * @property int $overage_price
 * @property string $overage_currency
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PlanFeature extends Pivot
{


    /**
     * @var array<int, string> The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'feature_id',
        'value',
        'is_soft_limit',
        'overage_price',
        'overage_currency',
    ];

    protected $casts = [
        'is_soft_limit' => 'boolean',
        'overage_price' => 'integer',
        'overage_currency' => 'string',
    ];

    /**
     * @var string $table The table associated with the model.
     */
    protected $table;

    /**
     * PlanFeature constructor.
     *
     * Sets the table name dynamically from configuration.
     *
     * @param array $attributes Initial attributes for the model instance
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('plan_feature','plan_feature');
    }

   

    /**
     * Get the plan that owns the price.
     *
     * @return BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('plan', Plan::class));
    }

     /**
     * Get the plan that owns the price.
     *
     * @return BelongsTo
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('feature', Feature::class));
    }
}
