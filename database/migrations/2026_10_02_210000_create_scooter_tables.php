<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scooter_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('unlock_fee', 10, 2)->default(10);
            $table->decimal('price_per_minute', 10, 2)->default(2);
            $table->decimal('minimum_amount', 10, 2)->default(20);
            $table->timestamps();
        });

        Schema::create('scooter_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scooters', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('scooter_station_id')->constrained('scooter_stations')->cascadeOnDelete();
            $table->unsignedTinyInteger('battery_percent')->default(100);
            $table->string('status')->default('available'); // available, rented, maintenance
            $table->timestamps();
        });

        Schema::create('scooter_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scooter_id')->constrained('scooters')->cascadeOnDelete();
            $table->foreignId('start_station_id')->constrained('scooter_stations');
            $table->foreignId('end_station_id')->nullable()->constrained('scooter_stations');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->decimal('unlock_fee', 10, 2);
            $table->decimal('price_per_minute', 10, 2);
            $table->decimal('minimum_amount', 10, 2);
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('payment_method')->default('cash');
            $table->string('payer_phone')->nullable();
            $table->string('payment_status')->default('pending'); // pending, confirmed
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->timestamps();
        });

        DB::table('scooter_settings')->insert([
            'unlock_fee' => 10,
            'price_per_minute' => 2,
            'minimum_amount' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stations = [
            ['name' => 'Baixa', 'address' => 'Praça da Independência, Maputo', 'latitude' => -25.969248, 'longitude' => 32.573176],
            ['name' => 'Polana', 'address' => 'Av. Julius Nyerere, Maputo', 'latitude' => -25.964800, 'longitude' => 32.589400],
            ['name' => 'Costa do Sol', 'address' => 'Av. Marginal, Maputo', 'latitude' => -25.935500, 'longitude' => 32.620100],
        ];

        foreach ($stations as $index => $station) {
            $stationId = DB::table('scooter_stations')->insertGetId([
                ...$station,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            for ($n = 1; $n <= 3; $n++) {
                DB::table('scooters')->insert([
                    'code' => sprintf('TRT-%02d%02d', $index + 1, $n),
                    'scooter_station_id' => $stationId,
                    'battery_percent' => 70 + ($n * 8),
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scooter_rentals');
        Schema::dropIfExists('scooters');
        Schema::dropIfExists('scooter_stations');
        Schema::dropIfExists('scooter_settings');
    }
};
