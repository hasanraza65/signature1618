<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('card_last_4')->nullable();
            $table->string('card_expiry_month')->nullable();
            $table->string('card_expiry_year')->nullable();
            $table->string('card_brand')->nullable();
            $table->integer('is_default')->default(1);
            $table->string('stripe_pm_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
