<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            if (! Schema::hasColumn('room_types', 'deposit_percent')) {
                $table->unsignedTinyInteger('deposit_percent')->default(30)->after('free_cancel_days');
            }
        });

        Schema::table('hotel_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('hotel_bookings', 'payment_mode')) {
                $table->string('payment_mode', 16)->default('full')->after('total_price');
            }
            if (! Schema::hasColumn('hotel_bookings', 'deposit_amount')) {
                $table->decimal('deposit_amount', 10, 2)->default(0)->after('payment_mode');
            }
            if (! Schema::hasColumn('hotel_bookings', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('deposit_amount');
            }
            if (! Schema::hasColumn('hotel_bookings', 'qr_payload')) {
                $table->string('qr_payload', 64)->nullable()->after('checkin_code');
            }
            if (! Schema::hasColumn('hotel_bookings', 'confirm_deadline_at')) {
                $table->timestamp('confirm_deadline_at')->nullable()->after('confirmed_at');
            }
            if (! Schema::hasColumn('hotel_bookings', 'no_show_at')) {
                $table->timestamp('no_show_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('hotel_bookings', 'reject_reason')) {
                $table->string('reject_reason')->nullable()->after('notes');
            }
        });

        if (Schema::hasTable('room_date_blocks') && ! Schema::hasColumn('room_date_blocks', 'note')) {
            Schema::table('room_date_blocks', function (Blueprint $table) {
                $table->string('note')->nullable()->after('price_override');
            });
        }

        Schema::create('driver_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('license_number', 64)->nullable();
            $table->string('license_image')->nullable();
            $table->string('insurance_image')->nullable();
            $table->string('vehicle_reg_image')->nullable();
            $table->string('criminal_record_image')->nullable();
            $table->string('id_document_image')->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('status');
        });

        Schema::create('lost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description');
            $table->string('status', 20)->default('open'); // open|driver_contacted|returning|returned|closed|cancelled
            $table->decimal('return_fee', 10, 2)->default(0);
            $table->string('customer_phone', 20)->nullable();
            $table->text('driver_response')->nullable();
            $table->text('support_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_items');
        Schema::dropIfExists('driver_verifications');

        Schema::table('hotel_bookings', function (Blueprint $table) {
            foreach (['payment_mode', 'deposit_amount', 'amount_paid', 'qr_payload', 'confirm_deadline_at', 'no_show_at', 'reject_reason'] as $col) {
                if (Schema::hasColumn('hotel_bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::hasColumn('room_types', 'deposit_percent')) {
            Schema::table('room_types', function (Blueprint $table) {
                $table->dropColumn('deposit_percent');
            });
        }
    }
};
