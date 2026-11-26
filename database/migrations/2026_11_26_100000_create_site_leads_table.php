<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_leads', function (Blueprint $table) {
            $table->id();
            $table->string('formulario', 40);
            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone', 40)->nullable();
            $table->string('assunto')->nullable();
            $table->text('mensagem')->nullable();
            $table->json('payload')->nullable();
            $table->string('pagina')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_leads');
    }
};
