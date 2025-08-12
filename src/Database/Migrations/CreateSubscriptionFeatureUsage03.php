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
            $table->dropUnique('subs_feat_unique');
        });
    }

    public function down()
    {
      
    }
};
