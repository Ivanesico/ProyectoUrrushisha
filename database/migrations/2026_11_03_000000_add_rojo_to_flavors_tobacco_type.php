<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds "base" and "rojo" to the closed list of flavors.tobacco_type.
 *
 * Production already allows these 6 values and has flavors using them, even
 * though the original migration only lists 4, so the new list must include both.
 *
 * Only the list restriction changes: no row is updated or deleted.
 * $withinTransaction keeps its default value on purpose: the Migrator opens a
 * transaction in Postgres, and does not in SQLite, where the table rebuild
 * needs to run outside of one for the foreign keys PRAGMA to work.
 */
return new class extends Migration {

    private const OLD_VALUES = ['rubio', 'negro', 'herbal', 'sin_nicotina'];
    private const NEW_VALUES = ['rubio', 'negro', 'herbal', 'base', 'rojo', 'sin_nicotina'];
    private const ADDED_VALUES = ['rojo', 'base'];

    /**
     * Run the migrations.
     */
    public function up(): void {
        $this->changeAllowedValues(self::NEW_VALUES);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        foreach (self::ADDED_VALUES as $value) {
            $flavors = DB::table('flavors')->where('tobacco_type', $value)->count();

            if ($flavors > 0) {
                throw new RuntimeException("No se puede revertir: hay {$flavors} sabores con tipo de tabaco {$value}");
            }
        }

        $this->changeAllowedValues(self::OLD_VALUES);
    }

    /**
     * @param list<string> $values
     */
    private function changeAllowedValues(array $values): void {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
            case 'sqlite':
                Schema::table('flavors', function (Blueprint $table) use ($values) {
                    $table->enum('tobacco_type', $values)->nullable()->change();
                });
                break;

            case 'pgsql':
                $this->replacePostgresCheckConstraint($values);
                break;

            default:
                throw new RuntimeException("Unsupported database driver: {$driver}");
        }
    }

    /**
     * In Postgres the enum is a varchar with a CHECK constraint, and ->change()
     * would generate invalid SQL. The constraint name comes from the catalog
     * instead of being assumed, and IF EXISTS is not used on purpose: a wrong
     * name must fail instead of leaving the old list active.
     *
     * @param list<string> $values
     */
    private function replacePostgresCheckConstraint(array $values): void {
        foreach ($this->postgresCheckConstraints() as $constraint) {
            $name = str_replace('"', '""', $constraint->conname);

            DB::statement("ALTER TABLE flavors DROP CONSTRAINT \"{$name}\"");
        }

        $list = "'" . implode("','", $values) . "'";

        DB::statement("ALTER TABLE flavors ADD CONSTRAINT flavors_tobacco_type_check CHECK (tobacco_type IN ({$list}))");

        $this->verifyPostgresCheckConstraint($values === self::NEW_VALUES);
    }

    /**
     * Any failure here rolls back the Migrator's transaction.
     */
    private function verifyPostgresCheckConstraint(bool $expectsAddedValues): void {
        $constraints = $this->postgresCheckConstraints();

        if (count($constraints) !== 1) {
            throw new RuntimeException('Expected exactly one CHECK constraint on flavors.tobacco_type, found ' . count($constraints));
        }

        foreach (self::ADDED_VALUES as $value) {
            if (str_contains($constraints[0]->definition, "'{$value}'") !== $expectsAddedValues) {
                throw new RuntimeException("Unexpected CHECK constraint on flavors.tobacco_type: {$constraints[0]->definition}");
            }
        }
    }

    /**
     * @return list<object{conname: string, definition: string}>
     */
    private function postgresCheckConstraints(): array {
        return DB::select(
            "SELECT c.conname, pg_get_constraintdef(c.oid) AS definition
             FROM pg_constraint c
             JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'flavors'::regclass AND c.contype = 'c' AND a.attname = 'tobacco_type'"
        );
    }
};
