<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\Factory;
use NtechServices\SubscriptionSystem\Config\ConfigHelper;
use NtechServices\SubscriptionSystem\Database\Factories\PlanPriceFactory;
use PSpell\Config;

/**
 * Class PlanPrice
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
class PlanPrice extends Model
{
    use HasFactory;

    /**
     * @var array<int, string> The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'price',
        'currency',
        'billing_cycle',
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
        $this->table = ConfigHelper::getConfigTable('plan_prices','plan_prices');
    }

    /**
     * Creates a new factory instance for the PlanPrice model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return PlanPriceFactory::new();
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
