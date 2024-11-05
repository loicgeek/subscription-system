<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;



/**
 * Class PlanFeature
 *
 * Represents the pricing details for a specific subscription plan, including the price, currency, and billing cycle.
 *
 * @property int $id
 * @property int $plan_id
 * @property float $price
 * @property string $currency
 * @property string $billing_cycle
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PlanFeature extends Model
{


    /**
     * @var array<int, string> The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'feature_id',
        'value',
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
}
