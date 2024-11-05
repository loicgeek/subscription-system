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
        Schema::create(ConfigHelper::getConfigTable('subscription_histories','subscription_histories'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade'); // Add this line
            $table->string('status');
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('subscription_histories','subscription_histories'));
    }
};
