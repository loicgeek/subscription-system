<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;

class SubscriptionHistory extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('subscription_histories','subscription_histories');
    }
}
