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
        Schema::create('otp_charges', function (Blueprint $table) {
            $table->id();
            $table->integer('request_id')->nullable();
            $table->integer('signer_user_id')->nullable();
            $table->double('amount')->nullable();
            $table->integer('status')->default(1);
            $table->string('transaction_id')->nullable();
            $table->longText('stripe_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_charges');
    }
};
