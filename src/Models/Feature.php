<?php 

namespace NtechServices\SubscriptionSystem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = ConfigHelper::getConfigTable('features','features');
    }
    

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
