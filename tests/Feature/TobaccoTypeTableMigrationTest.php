<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Spec 012, RF1: la migración que pasa los tipos de tabaco de la columna con lista cerrada
 * a la tabla tobacco_types. Como TobaccoTypeMigrationTest, no usa RefreshDatabase
 * (SQLite ignora el PRAGMA de claves foráneas dentro de una transacción) y solo corre en
 * SQLite en memoria.
 */
class TobaccoTypeTableMigrationTest extends TestCase
{
    private const MIGRATION = '2026_11_10_000003_move_tobacco_types_to_table';

    // Valor antiguo => nombre nuevo
    private const NAMES = [
        'rubio' => 'Rubio',
        'negro' => 'Negro',
        'herbal' => 'Herbal',
        'base' => 'Base',
        'rojo' => 'Rojo',
        'sin_nicotina' => 'Sin nicotina',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            $this->fail('Este test solo se ejecuta en SQLite en memoria.');
        }
    }

    /**
     * Ejecuta las migraciones anteriores a la de esta spec (la base de datos queda como producción hoy).
     */
    private function migrateBefore(): void
    {
        $paths = collect(glob(database_path('migrations/*.php')))
            ->filter(fn ($path) => strcmp(basename($path, '.php'), self::MIGRATION) < 0)
            ->sort()
            ->values()
            ->all();

        $this->assertSame(0, Artisan::call('migrate', ['--path' => $paths, '--realpath' => true]));
        $this->assertTrue(Schema::hasColumn('flavors', 'tobacco_type'));
    }

    private function migrateThis(): void
    {
        $path = database_path('migrations/'.self::MIGRATION.'.php');
        $this->assertSame(0, Artisan::call('migrate', ['--path' => [$path], '--realpath' => true]));
    }

    private function migration(): object
    {
        return require database_path('migrations/'.self::MIGRATION.'.php');
    }

    /**
     * Sabores con los 6 tipos antiguos, uno sin tipo y datos en las tablas relacionadas.
     */
    private function seedOldData(): void
    {
        DB::table('users')->insert(['id' => 1, 'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'hash', 'is_admin' => 0]);
        DB::table('brands')->insert(['id' => 1, 'name' => 'Marca']);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Categoría']);

        $id = 1;
        foreach ([...array_keys(self::NAMES), 'rubio', null] as $type) {
            DB::table('flavors')->insert([
                'id' => $id, 'brand_id' => 1, 'category_id' => 1, 'name' => "Sabor {$id}",
                'tobacco_type' => $type, 'is_public' => 1,
                'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00',
            ]);
            $id++;
        }

        DB::table('favorites')->insert(['user_id' => 1, 'flavor_id' => 1, 'created_at' => '2026-01-02 10:00:00', 'updated_at' => '2026-01-02 10:00:00']);
        DB::table('mixes')->insert(['id' => 1, 'user_id' => 1, 'name' => 'Mezcla', 'is_public' => 1]);
        DB::table('mix_items')->insert(['id' => 1, 'mix_id' => 1, 'flavor_id' => 2, 'ratio' => 100]);
    }

    /**
     * @return array<int, ?string> id del sabor => nombre de su tipo
     */
    private function typeNamesByFlavor(): array
    {
        return DB::table('flavors')
            ->leftJoin('tobacco_types', 'tobacco_types.id', '=', 'flavors.tobacco_type_id')
            ->orderBy('flavors.id')
            ->pluck('tobacco_types.name', 'flavors.id')
            ->all();
    }

    public function test_it_creates_the_six_types_and_assigns_each_flavor_its_type(): void
    {
        $this->migrateBefore();
        $this->seedOldData();

        $this->migrateThis();

        $this->assertEqualsCanonicalizing(array_values(self::NAMES), DB::table('tobacco_types')->pluck('name')->all());
        $this->assertSame(
            [1 => 'Rubio', 2 => 'Negro', 3 => 'Herbal', 4 => 'Base', 5 => 'Rojo', 6 => 'Sin nicotina', 7 => 'Rubio', 8 => null],
            $this->typeNamesByFlavor()
        );
        $this->assertFalse(Schema::hasColumn('flavors', 'tobacco_type'));
    }

    public function test_it_keeps_the_rest_of_the_data(): void
    {
        $this->migrateBefore();
        $this->seedOldData();
        $flavorsBefore = DB::table('flavors')->orderBy('id')->get(['id', 'brand_id', 'category_id', 'name', 'is_public', 'created_at'])->toArray();

        $this->migrateThis();

        $this->assertEquals($flavorsBefore, DB::table('flavors')->orderBy('id')->get(['id', 'brand_id', 'category_id', 'name', 'is_public', 'created_at'])->toArray());
        $this->assertSame(1, DB::table('favorites')->count());
        $this->assertSame(1, DB::table('mix_items')->count());
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
    }

    public function test_the_database_does_not_allow_deleting_a_type_in_use_or_an_unknown_type(): void
    {
        $this->migrateBefore();
        $this->seedOldData();
        $this->migrateThis();

        $rubio = DB::table('tobacco_types')->where('name', 'Rubio')->value('id');

        $this->assertDatabaseRejects(fn () => DB::table('tobacco_types')->where('id', $rubio)->delete());
        $this->assertDatabaseRejects(fn () => DB::table('flavors')->where('id', 1)->update(['tobacco_type_id' => 999]));
        $this->assertDatabaseRejects(fn () => DB::table('tobacco_types')->insert(['name' => 'Rubio']));
    }

    public function test_down_restores_the_old_column_and_values(): void
    {
        $this->migrateBefore();
        $this->seedOldData();
        $before = DB::table('flavors')->orderBy('id')->pluck('tobacco_type', 'id')->all();
        $this->migrateThis();

        $this->migration()->down();

        $this->assertSame($before, DB::table('flavors')->orderBy('id')->pluck('tobacco_type', 'id')->all());
        $this->assertFalse(Schema::hasColumn('flavors', 'tobacco_type_id'));
        $this->assertFalse(Schema::hasTable('tobacco_types'));
        // La lista cerrada vuelve a estar activa.
        $this->assertDatabaseRejects(fn () => DB::table('flavors')->where('id', 1)->update(['tobacco_type' => 'virginia']));
    }

    public function test_down_fails_with_a_clear_message_if_a_new_type_is_in_use(): void
    {
        $this->migrateBefore();
        $this->seedOldData();
        $this->migrateThis();

        $new = DB::table('tobacco_types')->insertGetId(['name' => 'Virginia']);
        DB::table('flavors')->where('id', 1)->update(['tobacco_type_id' => $new]);

        try {
            $this->migration()->down();
            $this->fail('El down debería haberse rechazado.');
        } catch (RuntimeException $exception) {
            $this->assertSame('No se puede revertir: hay sabores con tipos de tabaco que no existían antes (Virginia).', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('tobacco_types'));
        $this->assertSame('Virginia', $this->typeNamesByFlavor()[1]);
    }

    private function assertDatabaseRejects(callable $operation): void
    {
        try {
            $operation();
        } catch (\Illuminate\Database\QueryException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('La base de datos debería haber rechazado la operación.');
    }
}
