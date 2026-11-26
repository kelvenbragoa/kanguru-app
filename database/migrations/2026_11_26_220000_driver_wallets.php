<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('driver_type', 20)->default('independent'); // independent|fleet
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('min_balance', 12, 2)->nullable(); // null = usa config
            $table->boolean('wallet_blocked')->default(false);
            $table->timestamp('last_topup_at')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30); // topup|commission|adjustment|refund
            $table->decimal('amount', 12, 2); // + crédito / - débito
            $table->decimal('balance_after', 12, 2);
            $table->string('status', 20)->default('completed'); // pending|completed|rejected
            $table->string('method', 30)->nullable(); // mpesa|emola|cash_office|card|system
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('external_ref')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_wallet_transactions');
        Schema::dropIfExists('driver_wallets');
    }
};
