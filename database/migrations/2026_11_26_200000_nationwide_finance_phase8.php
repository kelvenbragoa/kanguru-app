<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('city', 64)->nullable()->after('address');
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('commission_rate', 5, 4)->nullable()->after('delivery_fee');
            $table->boolean('is_featured')->default(false)->after('commission_rate');
            $table->string('subscription_plan', 32)->nullable()->after('is_featured');
            $table->boolean('is_paused')->default(false)->after('subscription_plan');
            $table->index(['city', 'type']);
            $table->index(['is_featured', 'type']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('service_fee', 10, 2)->default(0)->after('delivery_fee');
            $table->decimal('cancel_fee', 10, 2)->default(0)->after('service_fee');
            $table->decimal('wait_fee', 10, 2)->default(0)->after('cancel_fee');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('wait_fee');
            $table->decimal('partner_due', 10, 2)->default(0)->after('commission_amount');
            $table->decimal('courier_due', 10, 2)->default(0)->after('partner_due');
            $table->timestamp('settled_at')->nullable()->after('courier_due');
            $table->timestamp('arrived_partner_at')->nullable()->after('collected_at');
            $table->timestamp('arrived_customer_at')->nullable()->after('arrived_partner_at');
            $table->timestamp('accept_deadline_at')->nullable()->after('shop_accepted_at');
        });

        if (! Schema::hasColumn('profiles', 'cash_blocked')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->boolean('cash_blocked')->default(false)->after('notify_sms');
            });
        }

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type', 40);
            $table->unsignedBigInteger('reference_id');
            $table->string('beneficiary_type', 20); // partner|courier|platform
            $table->unsignedBigInteger('beneficiary_id')->nullable();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('MZN');
            $table->string('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending|payable|paid|void
            $table->foreignId('payout_id')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['beneficiary_type', 'beneficiary_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('beneficiary_type', 20);
            $table->unsignedBigInteger('beneficiary_id')->nullable();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('open'); // open|paid
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->foreign('payout_id')->references('id')->on('payouts')->nullOnDelete();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('delta');
            $table->unsignedInteger('stock_after')->nullable();
            $table->string('reason', 40);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['shop_id', 'created_at']);
        });

        Schema::create('room_date_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('blocked_units')->default(0);
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['room_type_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_date_blocks');
        Schema::dropIfExists('stock_movements');
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_id');
        });
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('ledger_entries');

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['cash_blocked']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'service_fee', 'cancel_fee', 'wait_fee', 'commission_amount',
                'partner_due', 'courier_due', 'settled_at',
                'arrived_partner_at', 'arrived_customer_at', 'accept_deadline_at',
            ]);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'city', 'latitude', 'longitude', 'commission_rate',
                'is_featured', 'subscription_plan', 'is_paused',
            ]);
        });
    }
};
