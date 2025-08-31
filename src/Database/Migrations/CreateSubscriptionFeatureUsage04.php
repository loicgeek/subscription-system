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
            $table->string('limit')->nullable();
        });
    }

    public function down()
    {
        Schema::table(ConfigHelper::getConfigTable('subscription_feature_usage'), function (Blueprint $table) {
            $table->dropColumn('limit');
        });
    }
};
