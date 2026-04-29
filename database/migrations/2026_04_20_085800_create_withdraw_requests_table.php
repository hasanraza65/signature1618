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
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 10, 2);

            // payment details (flexible)
            $table->string('method')->nullable(); // bank, jazzcash, easypaisa
            $table->string('account_number')->nullable();
            $table->string('account_title')->nullable();

            // status handling
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])
                ->default('pending');

            $table->text('admin_note')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
    }
};
