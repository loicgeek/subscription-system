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
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
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
