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
        Schema::create('ecosystem_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecosystem_id')->constrained('ecosystems')->onDelete('cascade');
            $table->string('key'); // chat_console, training_center, quick_queries
            $table->string('name'); // Centro de Entrenamiento IA
            $table->string('icon')->default('fa-layer-group');
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
        Schema::dropIfExists('ecosystem_modules');
    }
};
