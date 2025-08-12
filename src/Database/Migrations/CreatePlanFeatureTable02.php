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
        Schema::table(ConfigHelper::getConfigTable('plan_feature'), function (Blueprint $table) {
           $table->boolean('is_soft_limit')->nullable()->default(false);
           $table->integer('overage_price')->nullable();
           $table->string('overage_currency')->nullable();
        });
    }

    public function down()
    {
        Schema::table(ConfigHelper::getConfigTable('plan_feature'), function (Blueprint $table) {
            $table->dropColumn('is_soft_limit');
            $table->dropColumn('overage_price');
            $table->dropColumn('overage_currency');
        });
    }
};
