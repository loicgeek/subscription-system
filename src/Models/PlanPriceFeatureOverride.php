<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;

class PlanPriceFeatureOverride extends Model 
{

    protected $fillable = [
        'plan_price_id',
        'feature_id',
        'value',
        'is_soft_limit',
        'overage_price',
        'overage_currency',
    ];


        /**
     * @var string $table The table associated with the model.
     */
    protected $table;

    /**
     * PlanPrice constructor.
     *
     * Sets the table name dynamically from configuration.
     *
     * @param array $attributes Initial attributes for the model instance
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('plan_price_feature_overrides','plan_price_feature_overrides');
    }

    
    public function planPrice()
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('plan_price', PlanPrice::class));
    }
    
    public function feature()
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('feature', Feature::class));
    }
}