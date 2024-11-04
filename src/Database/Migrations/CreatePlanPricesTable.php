<?php 
namespace NtechSetvices\SubscriptionSystem\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(ConfigHelper::getConfigTable('plan_prices'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade'); // Foreign key referencing plans table
            $table->decimal('price', 10, 2); // Price for the plan
            $table->string('currency'); // Currency for the price
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly']); // Billing cycle
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('plan_prices')); // Drop the table if it exists
    }
};
