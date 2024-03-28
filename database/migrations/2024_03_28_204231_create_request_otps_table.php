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
        Schema::create('request_otps', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_unique_id')->nullable();
            $table->string('otp')->nullable();
            $table->string('type')->default('email'); // email or sms
            $table->integer('request_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_otps');
    }
};
