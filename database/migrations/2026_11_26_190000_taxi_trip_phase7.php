<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('emergency_at')->nullable()->after('delivered_at');
            $table->unsignedTinyInteger('customer_rating')->nullable()->after('emergency_at');
            $table->unsignedTinyInteger('driver_rating')->nullable()->after('customer_rating');
            $table->text('customer_rating_comment')->nullable()->after('driver_rating');
            $table->text('driver_rating_comment')->nullable()->after('customer_rating_comment');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'emergency_at',
                'customer_rating',
                'driver_rating',
                'customer_rating_comment',
                'driver_rating_comment',
            ]);
        });
    }
};
