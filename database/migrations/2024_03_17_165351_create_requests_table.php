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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('file')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status')->default('draft');
            $table->string('unique_id')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('sms_otp')->default(0);
            $table->integer('email_otp')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
