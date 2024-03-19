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
        Schema::create('request_fields', function (Blueprint $table) {
            $table->id();
            $table->integer('request_id')->nullable();
            $table->string('type')->nullable();
            $table->string('x')->nullable();
            $table->string('y')->nullable();
            $table->string('height')->nullable();
            $table->string('width')->nullable();
            $table->string('recipientId')->nullable();
            $table->longText('question')->nullable();
            $table->integer('is_required')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_fields');
    }
};
