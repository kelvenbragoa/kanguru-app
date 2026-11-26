<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price_per_night', 10, 2);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('free_cancel_days')->default(1);
            $table->timestamps();
        });

        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('checkin_code', 4);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained('room_types')->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('guests')->default(1);
            $table->unsignedInteger('nights');
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('status', 32)->default('pending');
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->string('payer_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['room_type_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_bookings');
        Schema::dropIfExists('room_types');
    }
};
