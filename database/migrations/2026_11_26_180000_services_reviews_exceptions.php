<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 64)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('location_type', 20)->default('onsite'); // onsite|remote|both
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shop_id', 'is_active']);
        });

        Schema::create('service_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('completion_code', 4);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('service_offering_id')->constrained('service_offerings')->restrictOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('status', 32)->default('pending');
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->string('payer_phone', 20)->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('order_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('type', 64);
            $table->text('description')->nullable();
            $table->string('cost_bearer', 20)->default('none'); // none|customer|partner|kanguro
            $table->decimal('cost_amount', 10, 2)->default(0);
            $table->string('status', 20)->default('open'); // open|resolved
            $table->string('photo_url')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['type', 'status']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('rider_rating')->nullable()->after('rating');
            $table->foreignId('service_booking_id')->nullable()->after('order_id')
                ->constrained('service_bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_booking_id');
            $table->dropColumn('rider_rating');
        });
        Schema::dropIfExists('order_exceptions');
        Schema::dropIfExists('service_bookings');
        Schema::dropIfExists('service_offerings');
    }
};
