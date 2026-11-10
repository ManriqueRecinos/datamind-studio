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
        Schema::create('ecosystems', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // chat, vectorization, settings
            $table->string('name'); // Ecosistema de Chat & Analítica
            $table->string('short_name'); // Chat & RAG
            $table->text('description')->nullable(); // Subtítulo para las tarjetas del Hub
            $table->string('icon')->default('fa-comments'); // fa-comments, fa-database, fa-gear
            $table->string('color_preset')->default('ocean'); // ocean, emerald, indigo, amber, graphite
            $table->string('badge_text')->nullable(); // Módulo 1, RAG & SQL
            $table->string('route_name')->default('chat.index'); // chat.index, connections.index, settings.index
            $table->integer('order_num')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecosystems');
    }
};
