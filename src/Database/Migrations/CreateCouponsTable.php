<?php 
namespace NtechServices\SubscriptionSystem\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NtechServices\SubscriptionSystem\Config\ConfigHelper;

return new class  extends Migration
{
    public function up()
    {
        Schema::create(ConfigHelper::getConfigTable('coupons','coupons')  , function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('discount_amount', 8, 2);
            $table->enum('discount_type', ['fixed', 'percentage']); // Type of discount
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(ConfigHelper::getConfigTable('coupons','couponse'));
    }
};
