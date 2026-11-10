<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 008: una mezcla guardada desde "Públicas" recuerda de qué mezcla es copia.
 * Nullable: las mezclas que ya existen (y las creadas a mano) no tienen origen.
 * Si se borra la original, la copia se queda sin origen. El único impide dos copias
 * de la misma mezcla por usuario (varios null sí se admiten en Postgres, MySQL y SQLite).
 */
return new class extends Migration {

    public function up(): void {
        Schema::table('mixes', function (Blueprint $table) {
            $table->foreignId('source_mix_id')->nullable()->after('is_public')->constrained('mixes')->nullOnDelete();
            $table->unique(['user_id', 'source_mix_id']);
        });
    }

    public function down(): void {
        // MySQL usa el único (user_id, source_mix_id) para la clave foránea de user_id y quita
        // el índice que tenía: hay que devolvérselo, con su nombre de antes, para poder borrar el único.
        // Postgres y SQLite no crean ese índice, así que allí no se añade.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('mixes', function (Blueprint $table) {
                $table->index('user_id', 'mixes_user_id_foreign');
            });
        }

        Schema::table('mixes', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'source_mix_id']);
            $table->dropConstrainedForeignId('source_mix_id');
        });
    }
};
