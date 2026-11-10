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
        Schema::create('ecosystem_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('ecosystem_modules')->onDelete('cascade');
            $table->string('name'); // Nueva Consulta, Entrenar Relación
            $table->string('icon')->default('fa-circle-dot');
            $table->string('route_or_action'); // route:chat.index o #btnOpenTrainingCenter o #paletteSection
            $table->string('action_type')->default('route'); // route, anchor, modal, trigger
            $table->string('badge_label')->nullable(); // Pro, Nuevo, etc.
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
        Schema::dropIfExists('ecosystem_interfaces');
    }
};
