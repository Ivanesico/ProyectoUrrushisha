<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 012: los tipos de tabaco pasan de la columna flavors.tobacco_type (lista cerrada) a la
 * tabla tobacco_types, que gestiona el admin; cada sabor apunta a su tipo con tobacco_type_id.
 *
 * Producción (comprobado el 2026-11-10): restricción flavors_tobacco_type_check con los 6 valores
 * de abajo, 389 sabores todos con un valor de la lista y ningún nulo.
 *
 * Antes de quitar la columna antigua se comprueba que ningún sabor ha perdido su tipo. En Postgres
 * la migración va en una transacción: si algo falla, no queda a medias. Al quitar la columna,
 * su restricción CHECK desaparece con ella.
 */
return new class extends Migration {

    // Valor antiguo => nombre legible (el mismo que mostraba Flavor::tobaccoTypeLabel).
    private const NAMES = [
        'rubio' => 'Rubio',
        'negro' => 'Negro',
        'herbal' => 'Herbal',
        'base' => 'Base',
        'rojo' => 'Rojo',
        'sin_nicotina' => 'Sin nicotina',
    ];

    public function up(): void {
        Schema::create('tobacco_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->timestamps();
        });

        $now = now();
        $ids = [];
        foreach (self::NAMES as $old => $name) {
            $ids[$old] = DB::table('tobacco_types')->insertGetId(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }

        Schema::table('flavors', function (Blueprint $table) {
            $table->foreignId('tobacco_type_id')->nullable()->constrained('tobacco_types')->restrictOnDelete();
        });

        foreach ($ids as $old => $id) {
            DB::table('flavors')->where('tobacco_type', $old)->update(['tobacco_type_id' => $id]);
        }

        $lost = DB::table('flavors')->whereNotNull('tobacco_type')->whereNull('tobacco_type_id')->count();
        if ($lost > 0) {
            throw new RuntimeException("No se puede migrar: {$lost} sabores tienen un tipo de tabaco que no está en la lista.");
        }

        Schema::table('flavors', function (Blueprint $table) {
            $table->dropColumn('tobacco_type');
        });
    }

    public function down(): void {
        $unknown = DB::table('flavors')
                ->join('tobacco_types', 'tobacco_types.id', '=', 'flavors.tobacco_type_id')
                ->whereNotIn('tobacco_types.name', array_values(self::NAMES))
                ->distinct()
                ->orderBy('tobacco_types.name')
                ->pluck('tobacco_types.name');

        if ($unknown->isNotEmpty()) {
            throw new RuntimeException('No se puede revertir: hay sabores con tipos de tabaco que no existían antes (' . $unknown->implode(', ') . ').');
        }

        // enum: en MySQL y SQLite, columna enum; en Postgres, varchar con la restricción
        // flavors_tobacco_type_check (el nombre que le da Postgres, el mismo que había).
        Schema::table('flavors', function (Blueprint $table) {
            $table->enum('tobacco_type', array_keys(self::NAMES))->nullable();
        });

        foreach (self::NAMES as $old => $name) {
            $id = DB::table('tobacco_types')->where('name', $name)->value('id');

            if ($id !== null) {
                DB::table('flavors')->where('tobacco_type_id', $id)->update(['tobacco_type' => $old]);
            }
        }

        Schema::table('flavors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tobacco_type_id');
        });

        // SQLite no aplica la lista cerrada a una columna añadida con ALTER TABLE, y quitar la
        // clave foránea rehace la tabla: al final se rehace con change(), como en add_rojo.
        // MySQL y Postgres ya la tienen.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('flavors', function (Blueprint $table) {
                $table->enum('tobacco_type', array_keys(self::NAMES))->nullable()->change();
            });
        }

        Schema::dropIfExists('tobacco_types');
    }
};
