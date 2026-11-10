<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 010: precios de cada sabor por cantidad (gramos). Un sabor puede tener varios formatos,
 * con un solo precio por cantidad. Al borrar el sabor se borran sus precios.
 */
return new class extends Migration {

    public function up(): void {
        Schema::create('flavor_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flavor_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('grams');
            $table->decimal('price', 8, 2);
            $table->timestamps();

            $table->unique(['flavor_id', 'grams']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('flavor_prices');
    }
};
