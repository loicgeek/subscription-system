<?php 

namespace NtechServices\SubscriptionSystem\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NtechServices\SubscriptionSystem\Helpers\ConfigHelper;

return new class extends Migration
{
    public function up()
    {
        Schema::create(ConfigHelper::getConfigTable('plan_price_feature_overrides'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_price_id')->references('id')->on(ConfigHelper::getConfigTable('plan_prices'))->onDelete('cascade');
            $table->unsignedBigInteger('feature_id')->references('id')->on(ConfigHelper::getConfigTable('features'))->onDelete('cascade');
            $table->string('value'); // Override value for this specific plan price
            $table->boolean('is_soft_limit')->nullable()->default(false);
            $table->integer('overage_price')->nullable();
            $table->string('overage_currency')->nullable();
            $table->timestamps();
            
            // Ensure unique combination
            $table->unique(['plan_price_id', 'feature_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('plan_price_feature_overrides')); // Drop the table if it exists
    }
};
