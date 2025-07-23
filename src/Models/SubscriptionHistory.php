<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;

class SubscriptionHistory extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('subscription_histories','subscription_histories');
    }
    
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('subscription', Subscription::class));
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('plan', Plan::class));
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('plan_price', PlanPrice::class));
    }


}
