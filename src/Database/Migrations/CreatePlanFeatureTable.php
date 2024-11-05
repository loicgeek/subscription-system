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
        Schema::create(ConfigHelper::getConfigTable('plan_feature'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id')->references('id')->on(ConfigHelper::getConfigTable('plans'))->onDelete('cascade'); // Foreign key referencing plans table
            $table->unsignedBigInteger('feature_id')->references('id')->on(ConfigHelper::getConfigTable('features'))->onDelete('cascade'); // Foreign key referencing features table
            $table->string('value'); // Specific value for the feature associated with the plan
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('plan_feature')); // Drop the table if it exists
    }
};
