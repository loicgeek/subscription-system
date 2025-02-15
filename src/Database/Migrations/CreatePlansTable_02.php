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
        Schema::table(ConfigHelper::getConfigTable('plans'), function (Blueprint $table) {
            $table->boolean('popular')->nullable()->default(false);
           
        });
    }

    public function down()
    {
        Schema::table(ConfigHelper::getConfigTable('plans'), function (Blueprint $table) {
            $table->dropColumn('popular');
           
        });
    }
};
