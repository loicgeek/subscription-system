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
        Schema::table(ConfigHelper::getConfigTable('subscriptions'), function (Blueprint $table) {
            $table->timestamp('last_billing_date')->nullable()->after('next_billing_date');
            $table->json('billing_history')->nullable()->after('last_billing_date'); // Optional: track billing history
        });
    }

    public function down()
    {
       Schema::table(ConfigHelper::getConfigTable('subscriptions'), function (Blueprint $table) {
            $table->dropColumn('last_billing_date');
            $table->dropColumn('billing_history');
        });
    }
};
