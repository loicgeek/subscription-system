<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use NtechServices\SubscriptionSystem\Config\ConfigHelper;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_amount',
        'discount_type',
        'expires_at',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('coupons','coupons');
    }
}
