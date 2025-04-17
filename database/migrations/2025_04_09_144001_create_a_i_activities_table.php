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
        Schema::create('ai_activities', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('activity_type')->nullable();
            $table->longText('mail_subject')->nullable();
            $table->longText('mail_content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_activities');
    }
};
