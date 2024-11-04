<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use NtechServices\SubscriptionSystem\Config\ConfigHelper;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * The plans that belong to the feature.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(ConfigHelper::getConfigClass('plan', Plan::class))
                    ->withPivot('value')
                    ->withTimestamps();
    }
}
