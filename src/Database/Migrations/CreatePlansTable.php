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
        Schema::create(ConfigHelper::getConfigTable('plans'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->after('name'); // Assuming 'name' is the previous column
            $table->text('description')->nullable();
            $table->integer('trial_value')->default(0); // Number of trial days or units
            $table->enum('trial_cycle', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly'); // Trial cycle
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('plans'));
    }
};
