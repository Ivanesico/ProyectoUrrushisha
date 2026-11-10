<?php

namespace Tests\Feature;

use App\Models\Flavor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\AssertionFailedError;
use RuntimeException;
use Tests\TestCase;

/**
 * RF-50, RF-51 and RF-52.
 *
 * Does not use RefreshDatabase: SQLite ignores the foreign keys PRAGMA inside a
 * transaction, so rebuilding the table within one would lose favorites or fail
 * because of mix_items. This test runs and reverts migrations, so setUp() refuses any
 * database other than in-memory SQLite before anything is executed.
 */
class TobaccoTypeMigrationTest extends TestCase
{
    private const MIGRATION = '2026_11_03_000000_add_rojo_to_flavors_tobacco_type.php';

    private const TABLES_WITH_ORDER = [
        'users' => ['id'],
        'brands' => ['id'],
        'categories' => ['id'],
        'flavors' => ['id'],
        'favorites' => ['user_id', 'flavor_id'],
        'mixes' => ['id'],
        'mix_items' => ['id'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->failUnlessInMemorySqlite();
    }

    public function test_guard_rejects_any_database_other_than_in_memory_sqlite(): void
    {
        foreach (['mysql', 'pgsql'] as $connection) {
            $this->assertGuardFails(fn () => config(['database.default' => $connection]));
        }

        $this->assertGuardFails(function () {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => 'guard-check.sqlite']);
            DB::purge('sqlite');
        });
    }

    public function test_all_migrations_from_scratch_accept_base_and_rojo_and_reject_values_outside_the_list(): void
    {
        $this->migrate();
        $this->insertBrandAndCategory();

        foreach (['rubio', 'negro', 'herbal', 'base', 'rojo', 'sin_nicotina', null] as $index => $type) {
            $this->insertFlavor(100 + $index, $type);
        }

        $this->assertSame(1, DB::table('flavors')->where('tobacco_type', 'base')->count());
        $this->assertSame(1, DB::table('flavors')->where('tobacco_type', 'rojo')->count());
        $this->assertTobaccoTypeRejected('virginia');
        $this->assertTobaccoTypeRejected('ROJO');
    }

    // Spec 012: la constante Flavor::TOBACCO_TYPES ya no existe (los tipos están en tobacco_types);
    // se comprueba la lista de add_rojo directamente.
    public function test_the_add_rojo_list_has_the_six_values(): void
    {
        $this->migrate();
        $this->insertBrandAndCategory();

        foreach (['rubio', 'negro', 'herbal', 'base', 'rojo', 'sin_nicotina'] as $index => $type) {
            $this->insertFlavor(100 + $index, $type);
        }

        $this->assertSame(6, DB::table('flavors')->count());
    }

    public function test_migration_over_the_old_list_keeps_every_row(): void
    {
        $this->prepareOldListWithRows();
        $rowsBefore = $this->allRows();

        $this->assertNotEmpty($rowsBefore['favorites']);
        $this->assertNotEmpty($rowsBefore['mix_items']);

        $this->migrate();

        $this->assertSame($rowsBefore, $this->allRows());
        $this->insertFlavor(100, 'rojo');
        $this->insertFlavor(101, 'base');
    }

    /**
     * Production already allows the 6 values and has flavors with base and rojo.
     */
    public function test_migration_over_a_database_that_already_has_the_six_values_keeps_every_row(): void
    {
        $this->prepareOldListWithRows();
        $this->migrate();
        $this->insertFlavor(100, 'base');
        $this->insertFlavor(101, 'base');
        $this->insertFlavor(102, 'rojo');
        $rowsBefore = $this->allRows();

        $this->migrationInstance()->up();

        $this->assertSame($rowsBefore, $this->allRows());
        $this->insertFlavor(103, 'base');
        $this->insertFlavor(104, 'rojo');
        $this->assertTobaccoTypeRejected('virginia');
    }

    public function test_migration_over_the_old_list_keeps_columns_indexes_and_foreign_keys(): void
    {
        $this->prepareOldListWithRows();
        $schemaBefore = $this->schemaSnapshot();

        $this->assertNotEmpty($schemaBefore['flavors']['foreign_keys']);

        $this->migrate();

        $this->assertSame($schemaBefore, $this->schemaSnapshot());
    }

    public function test_foreign_keys_stay_active_after_the_migration(): void
    {
        $this->prepareOldListWithRows();
        $this->migrate();

        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));

        // Flavor used in a mix: restricted by mix_items.
        $this->assertDatabaseOperationFails(fn () => DB::table('flavors')->where('id', 1)->delete());
        $this->assertSame(1, DB::table('flavors')->where('id', 1)->count());

        // Flavor that is only a favorite: its favorite is deleted with it.
        DB::table('flavors')->where('id', 2)->delete();
        $this->assertSame(0, DB::table('favorites')->where('flavor_id', 2)->count());
        $this->assertSame(1, DB::table('favorites')->where('flavor_id', 1)->count());

        // A flavor cannot point to a brand that does not exist.
        $this->assertDatabaseOperationFails(fn () => $this->insertFlavor(100, 'rojo', brandId: 999));
    }

    public function test_is_public_keeps_its_default_value_after_the_migration(): void
    {
        $this->prepareOldListWithRows();
        $this->migrate();

        DB::table('flavors')->insert(['id' => 100, 'brand_id' => 1, 'name' => 'Sin indicar']);

        $this->assertSame(1, (int) DB::table('flavors')->where('id', 100)->value('is_public'));
    }

    public function test_revert_without_base_or_rojo_goes_back_to_four_values_and_keeps_every_row(): void
    {
        $this->prepareOldListWithRows();
        $this->migrate();
        $rowsBefore = $this->allRows();

        $this->migrationInstance()->down();

        $this->assertSame($rowsBefore, $this->allRows());
        $this->assertTobaccoTypeRejected('rojo');
        $this->assertTobaccoTypeRejected('base');

        foreach (['rubio', 'negro', 'herbal', 'sin_nicotina', null] as $index => $type) {
            $this->insertFlavor(100 + $index, $type);
        }
    }

    public function test_revert_with_rojo_fails_with_the_exact_message_and_changes_nothing(): void
    {
        $this->prepareOldListWithRows();
        $this->migrate();
        $this->insertFlavor(100, 'rojo');
        $this->insertFlavor(101, 'rojo');
        $rowsBefore = $this->allRows();

        try {
            $this->migrationInstance()->down();
            $this->fail('The revert should have been rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('No se puede revertir: hay 2 sabores con tipo de tabaco rojo', $exception->getMessage());
        }

        $this->assertSame($rowsBefore, $this->allRows());
        $this->insertFlavor(102, 'rojo');
    }

    public function test_revert_with_base_fails_with_the_exact_message_and_changes_nothing(): void
    {
        $this->prepareOldListWithRows();
        $this->migrate();
        $this->insertFlavor(100, 'base');
        $this->insertFlavor(101, 'base');
        $this->insertFlavor(102, 'base');
        $rowsBefore = $this->allRows();

        try {
            $this->migrationInstance()->down();
            $this->fail('The revert should have been rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('No se puede revertir: hay 3 sabores con tipo de tabaco base', $exception->getMessage());
        }

        $this->assertSame($rowsBefore, $this->allRows());
        $this->insertFlavor(103, 'base');
    }

    private function failUnlessInMemorySqlite(): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();

        if ($driver !== 'sqlite' || $database !== ':memory:') {
            $this->fail("This test only runs on in-memory SQLite, not on [{$driver}] [{$database}].");
        }
    }

    private function assertGuardFails(callable $changeConnection): void
    {
        $changeConnection();

        try {
            $this->failUnlessInMemorySqlite();
        } catch (AssertionFailedError) {
            $this->addToAssertionCount(1);

            return;
        }

        throw new AssertionFailedError('The guard accepted a database that is not in-memory SQLite.');
    }

    /**
     * Solo hasta add_rojo: la spec 012 quita la columna tobacco_type en una migración posterior.
     */
    private function migrate(): void
    {
        $paths = collect(glob(database_path('migrations/*.php')))
            ->filter(fn ($path) => strcmp(basename($path), self::MIGRATION) <= 0)
            ->sort()
            ->values()
            ->all();

        $this->assertSame(0, Artisan::call('migrate', ['--path' => $paths, '--realpath' => true]));
    }

    /**
     * Leaves the database with the old list of 4 values and rows in every table.
     */
    private function prepareOldListWithRows(): void
    {
        // Deshace solo esta migración (no "la última"): así no depende de las que vengan después (spec 008).
        $this->migrate();
        $this->migrationInstance()->down();
        DB::table('migrations')->where('migration', pathinfo(self::MIGRATION, PATHINFO_FILENAME))->delete();

        $this->insertBrandAndCategory();
        $this->assertTobaccoTypeRejected('rojo');
        $this->assertTobaccoTypeRejected('base');

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'hash', 'is_admin' => 0],
            ['id' => 2, 'name' => 'Luis', 'email' => 'luis@example.com', 'password' => 'hash', 'is_admin' => 1],
        ]);

        $this->insertFlavor(1, 'rubio');
        $this->insertFlavor(2, 'negro');
        $this->insertFlavor(3, 'herbal', isPublic: 0);
        $this->insertFlavor(4, 'sin_nicotina', categoryId: null);
        $this->insertFlavor(5, null);

        DB::table('favorites')->insert([
            ['user_id' => 1, 'flavor_id' => 1, 'created_at' => '2026-01-02 10:00:00', 'updated_at' => '2026-01-02 10:00:00'],
            ['user_id' => 1, 'flavor_id' => 2, 'created_at' => '2026-01-03 10:00:00', 'updated_at' => '2026-01-03 10:00:00'],
            ['user_id' => 2, 'flavor_id' => 3, 'created_at' => '2026-01-04 10:00:00', 'updated_at' => '2026-01-04 10:00:00'],
        ]);

        DB::table('mixes')->insert([
            ['id' => 1, 'user_id' => 1, 'name' => 'Mezcla pública', 'notes' => 'Con notas', 'is_public' => 1],
            ['id' => 2, 'user_id' => 2, 'name' => 'Mezcla privada', 'notes' => null, 'is_public' => 0],
        ]);

        DB::table('mix_items')->insert([
            ['id' => 1, 'mix_id' => 1, 'flavor_id' => 1, 'ratio' => 50],
            ['id' => 2, 'mix_id' => 1, 'flavor_id' => 3, 'ratio' => 50],
            ['id' => 3, 'mix_id' => 2, 'flavor_id' => 4, 'ratio' => 100],
        ]);
    }

    private function insertBrandAndCategory(): void
    {
        DB::table('brands')->insert(['id' => 1, 'name' => 'Marca']);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Categoría']);
    }

    private function insertFlavor(int $id, ?string $type, int $brandId = 1, ?int $categoryId = 1, int $isPublic = 1): void
    {
        DB::table('flavors')->insert([
            'id' => $id,
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'name' => "Sabor {$id}",
            'description' => "Descripción {$id}",
            'tobacco_type' => $type,
            'ingredients_text' => 'Arándano',
            'image_url' => "flavors/{$id}.jpg",
            'is_public' => $isPublic,
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 10:00:00',
        ]);
    }

    private function assertTobaccoTypeRejected(string $type): void
    {
        $this->assertDatabaseOperationFails(fn () => $this->insertFlavor(999, $type));
        $this->assertSame(0, DB::table('flavors')->where('id', 999)->count());
    }

    private function assertDatabaseOperationFails(callable $operation): void
    {
        try {
            $operation();
        } catch (QueryException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('The database should have rejected the operation.');
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function allRows(): array
    {
        $rows = [];

        foreach (self::TABLES_WITH_ORDER as $table => $orderColumns) {
            $query = DB::table($table);

            foreach ($orderColumns as $column) {
                $query->orderBy($column);
            }

            $rows[$table] = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return $rows;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function schemaSnapshot(): array
    {
        $snapshot = [];

        foreach (['flavors', 'favorites', 'mix_items'] as $table) {
            // The SQLite rebuild writes the foreign keys in another order: only their content matters.
            $foreignKeys = Schema::getForeignKeys($table);
            usort($foreignKeys, fn ($a, $b) => $a['columns'] <=> $b['columns']);

            $snapshot[$table] = [
                'columns' => Schema::getColumns($table),
                'indexes' => Schema::getIndexes($table),
                'foreign_keys' => $foreignKeys,
            ];
        }

        return $snapshot;
    }

    private function migrationInstance(): object
    {
        return require database_path('migrations/'.self::MIGRATION);
    }
}
