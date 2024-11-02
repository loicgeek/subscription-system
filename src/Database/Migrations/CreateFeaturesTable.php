<?php 

namespace NtechServices\SubscriptionSystem\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(config('subscription.tables.features'), function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Unique name for the feature
            $table->text('description')->nullable(); // Optional description of the feature
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('subscription.tables.features')); // Drop the table if it exists
    }
};
