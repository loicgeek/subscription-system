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
        Schema::table(ConfigHelper::getConfigTable('subscription_histories','subscription_histories'), function (Blueprint $table) {
           $table->unsignedBigInteger('plan_price_id')->references('id')->on(ConfigHelper::getConfigTable('plan_prices'))->onDelete('cascade')->nullable(); // Add this line
        });
    }

    public function down()
    {
        Schema::table(ConfigHelper::getConfigTable('subscription_histories','subscription_histories'), function (Blueprint $table) {
            $table->dropColumn('plan_price_id');
        });
    }
};
