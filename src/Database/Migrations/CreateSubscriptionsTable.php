<?php 

namespace NtechServices\SubscriptionSystem\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(ConfigHelper::getConfigTable('subscriptions'), function (Blueprint $table) {
            $table->id();
            $table->integer('subscribable_id'); 
            $table->string('subscribable_type'); 
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_price_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('trial_ends_at')->nullable();
            $table->date('next_billing_date');
            $table->decimal('amount_due', 10, 2);
            $table->string('currency');
            $table->string('status');
            $table->integer('grace_value')->nullable(); // Numerical grace value
            $table->enum('grace_cycle', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable(); // Grace cycle
            $table->decimal('prorated_amount', 10, 2)->nullable(); // Nullable to avoid issues with existing subscriptions
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');


            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('subscriptions'));
    }
};
