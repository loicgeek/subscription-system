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
 * Class SubscriptionFeatureUsage
 *
 * Represents the usage of a feature by a subscription.
 *
 * @property int $id
 * @property int $subscription_id
 * @property int $feature_id
 * @property int $used
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \NtechServices\SubscriptionSystem\Database\Factories\SubscriptionFeatureUsageFactory factory(...$parameters)
 */
 
 class SubscriptionFeatureUsage extends Model
 {

    protected $fillable = ['subscription_id', 
    'feature_id', 
    'used',
    'limit',
    'overage_count',
    'period_start',
    'period_end',
    'reset_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('subscription_feature_usage','subscription_feature_usage');
    }

    public function subscription()
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('subscription', Subscription::class));
    }

    public function feature()
    {
        return $this->belongsTo(ConfigHelper::getConfigClass('feature', Feature::class));
    }
    
 }