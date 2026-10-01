<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_online')->default(false)->after('is_active');
            $table->timestamp('last_seen_at')->nullable()->after('is_online');
            $table->decimal('last_latitude', 10, 8)->nullable()->after('last_seen_at');
            $table->decimal('last_longitude', 11, 8)->nullable()->after('last_latitude');
            $table->timestamp('location_updated_at')->nullable()->after('last_longitude');
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('body');
            $table->string('type');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token')->unique();
            $table->string('platform')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('app_notifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_online',
                'last_seen_at',
                'last_latitude',
                'last_longitude',
                'location_updated_at',
            ]);
        });
    }
};
