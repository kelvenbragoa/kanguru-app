<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('license_plate_number')->unique();
            $table->string('model');
            $table->string('color');
            $table->unsignedBigInteger('vehicle_type_id');
            $table->unsignedBigInteger('driver_id')->nullable(); // motorista atual
            $table->unsignedBigInteger('vehicle_status_id')->default(1);
            $table->decimal('capacity', 8, 2)->nullable(); // capacidade de carga em kg
            $table->text('description')->nullable();
            $table->year('year')->nullable();
            $table->timestamps();

            $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onDelete('restrict');
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('vehicle_status_id')->references('id')->on('vehicle_statuses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
