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
            try {
                $table->dropUnique('subs_feat_unique');
            } catch (\Exception $th) {
              
            }
            $table->dropUnique(['subscription_id', 'feature_id']);
        });
    }

    public function down()
    {
      
    }
};
