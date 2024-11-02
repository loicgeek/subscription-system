<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\Factory;
use NtechServices\SubscriptionSystem\Database\Factories\PlanPriceFactory;

class PlanPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'price',
        'currency',
        'billing_cycle',
    ];

    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('subscription.tables.plan_prices');
    }


    use HasFactory;
    protected static function newFactory(): Factory
    {
        return PlanPriceFactory::new();
    }

    /**
     * Get the plan that owns the price.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
