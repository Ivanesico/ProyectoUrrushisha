<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 011: valoración de 1 a 5 estrellas de cada usuario a cada sabor (una por usuario y sabor).
 * Si se borra el usuario o el sabor, se borran sus valoraciones.
 */
return new class extends Migration {

    public function up(): void {
        Schema::create('flavor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flavor_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('stars');
            $table->timestamps();

            $table->unique(['user_id', 'flavor_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('flavor_ratings');
    }
};
