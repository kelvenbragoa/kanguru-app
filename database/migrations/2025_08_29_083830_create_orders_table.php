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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // cliente
            $table->unsignedBigInteger('agent_user_id')->nullable(); // agente/motorista
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable(); // veículo designado
            $table->unsignedBigInteger('order_type_id');
            $table->string('origin');
            $table->string('destination');
            $table->unsignedBigInteger('origin_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id')->nullable();
            $table->unsignedBigInteger('order_status_id')->default(1);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('weight', 8, 2)->nullable(); // peso da carga
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // agendamento
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('agent_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
            $table->foreign('order_type_id')->references('id')->on('order_types')->onDelete('restrict');
            $table->foreign('order_status_id')->references('id')->on('order_statuses')->onDelete('restrict');
            $table->foreign('origin_location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('destination_location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
