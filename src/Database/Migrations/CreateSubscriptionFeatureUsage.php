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
        Schema::create(ConfigHelper::getConfigTable('subscription_feature_usage'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id')
                ->references('id')
                ->on(ConfigHelper::getConfigTable('subscriptions'))
                ->onDelete('cascade'); // Foreign key referencing subscriptions table
            $table->unsignedBigInteger('feature_id')
                ->references('id')
                ->on(ConfigHelper::getConfigTable('features'))
                ->onDelete('cascade'); // Foreign key referencing features table
            $table->unsignedInteger('used')->default(0);
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();
            $table->unique(['subscription_id', 'feature_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('subscription_feature_usage'));
    }
};
