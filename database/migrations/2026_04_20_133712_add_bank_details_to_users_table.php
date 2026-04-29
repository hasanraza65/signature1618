<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rib')->nullable();
            $table->string('bic')->nullable();
            $table->string('iban')->nullable();
            $table->string('rib_first_name')->nullable();
            $table->string('rib_last_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'rib',
                'bic',
                'iban',
                'rib_first_name',
                'rib_last_name',
            ]);
        });
    }
};
