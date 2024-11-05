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
        Schema::create(ConfigHelper::getConfigTable('features'), function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Unique name for the feature
            $table->text('description')->nullable(); // Optional description of the feature
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('features')); // Drop the table if it exists
    }
};
