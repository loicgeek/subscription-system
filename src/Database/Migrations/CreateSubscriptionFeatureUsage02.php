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
        Schema::table(ConfigHelper::getConfigTable('subscription_feature_usage'), function (Blueprint $table) {
            $table->unsignedBigInteger('overage_count')->nullable()->default(0);
            $table->dateTime('period_start')->nullable();
            $table->dateTime('period_end')->nullable();
        });
    }

    public function down()
    {
        Schema::table(ConfigHelper::getConfigTable('subscription_feature_usage'), function (Blueprint $table) {
            $table->dropColumn('overage_count');
            $table->dropColumn('period_start');
            $table->dropColumn('period_end');
        });
    }
};
