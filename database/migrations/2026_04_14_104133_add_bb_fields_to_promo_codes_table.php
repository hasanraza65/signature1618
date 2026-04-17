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
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->boolean('is_bb_promo')->default(0);
            $table->string('bb_user_email')->nullable();
            $table->unsignedBigInteger('bb_user_id')->nullable();
            $table->integer('generated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn([
                'is_bb_promo',
                'bb_user_email',
                'bb_user_id',
            ]);
        });
    }
};
