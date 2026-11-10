<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Favorite;
use App\Models\Flavor;
use App\Models\Mix;
use App\Models\MixItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * RF-56, RF-57, RF-65 and RF-66.
 */
class LocalSeedersTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_EMAIL = 'admin@urrushisha.test';
    private const USER_EMAIL = 'usuario@urrushisha.test';

    public function test_users_seeder_creates_an_admin_and_a_normal_user_in_local(): void
    {
        $this->app['env'] = 'local';

        $this->assertSame(0, $this->runSeeder('LocalTestUsersSeeder'));

        $admin = User::where('email', self::ADMIN_EMAIL)->firstOrFail();
        $user = User::where('email', self::USER_EMAIL)->firstOrFail();

        $this->assertTrue($admin->is_admin);
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_users_seeder_updates_existing_users_without_duplicating_them(): void
    {
        $this->app['env'] = 'local';
        User::factory()->create(['email' => self::ADMIN_EMAIL, 'is_admin' => false]);
        $otherUser = User::factory()->create();

        $this->runSeeder('LocalTestUsersSeeder');
        $this->runSeeder('LocalTestUsersSeeder');

        $this->assertSame(3, User::count());
        $this->assertTrue(User::where('email', self::ADMIN_EMAIL)->firstOrFail()->is_admin);
        $this->assertNotNull(User::find($otherUser->id));
    }

    public function test_users_seeder_creates_nothing_outside_local(): void
    {
        foreach (['testing', 'production'] as $environment) {
            $this->app['env'] = $environment;

            $this->assertSame(0, $this->runSeeder('LocalTestUsersSeeder'));

            $this->assertStringContainsString('solo se puede ejecutar en local', Artisan::output());
            $this->assertSame(0, User::count());
        }
    }

    public function test_data_seeder_creates_the_required_data_in_local(): void
    {
        $this->seedUsersAndData();

        $user = User::where('email', self::USER_EMAIL)->firstOrFail();

        $this->assertGreaterThanOrEqual(1, Brand::count());
        $this->assertGreaterThanOrEqual(1, DB::table('categories')->count());

        $publicFlavors = Flavor::where('is_public', true)->get();

        $this->assertGreaterThan(12, $publicFlavors->count());
        $this->assertTrue($publicFlavors->contains(fn ($flavor) => str_contains((string) $flavor->ingredients_text, 'Arándano')));
        $this->assertTrue($publicFlavors->contains(fn ($flavor) => in_array(mb_substr($flavor->name, 0, 1), ['Á', 'É', 'Í', 'Ó', 'Ú'], true)));
        $this->assertTrue($publicFlavors->contains('tobacco_type_id', $this->tobaccoType('rojo')));
        $this->assertGreaterThanOrEqual(1, Flavor::where('is_public', false)->count());

        $favoriteIds = Favorite::where('user_id', $user->id)->pluck('flavor_id');
        $this->assertNotEmpty($favoriteIds);

        $mix = Mix::where('user_id', $user->id)->firstOrFail();
        $items = MixItem::where('mix_id', $mix->id)->get();

        $this->assertCount(2, $items);
        $this->assertEquals([50, 50], $items->pluck('ratio')->map(fn ($ratio) => (float) $ratio)->all());
        $this->assertCount(1, $items->whereIn('flavor_id', $favoriteIds));
        $this->assertCount(1, $items->whereNotIn('flavor_id', $favoriteIds));
    }

    public function test_data_seeder_does_not_duplicate_when_repeated(): void
    {
        $this->seedUsersAndData();
        $rowsBefore = $this->seededRows();

        $this->assertSame(0, $this->runSeeder('LocalTestDataSeeder'));

        $this->assertSame($rowsBefore, $this->seededRows());
    }

    public function test_data_seeder_updates_its_data_and_deletes_nothing(): void
    {
        $this->seedUsersAndData();

        $otherFlavor = Flavor::factory()->create();
        $otherUser = User::factory()->create();
        Favorite::create(['user_id' => $otherUser->id, 'flavor_id' => $otherFlavor->id]);
        $otherMix = Mix::factory()->create(['user_id' => $otherUser->id]);
        $otherItem = MixItem::create(['mix_id' => $otherMix->id, 'flavor_id' => $otherFlavor->id, 'ratio' => 100]);

        $seededFlavor = Flavor::where('tobacco_type_id', $this->tobaccoType('rojo'))->firstOrFail();
        $originalDescription = $seededFlavor->description;
        $seededFlavor->update(['description' => 'Cambiada a mano']);

        $this->runSeeder('LocalTestDataSeeder');

        $this->assertSame($originalDescription, $seededFlavor->fresh()->description);
        $this->assertNotNull(Flavor::find($otherFlavor->id));
        $this->assertSame(1, Favorite::where('user_id', $otherUser->id)->where('flavor_id', $otherFlavor->id)->count());
        $this->assertNotNull(Mix::find($otherMix->id));
        $this->assertNotNull(MixItem::find($otherItem->id));
    }

    public function test_data_seeder_shows_an_error_and_creates_nothing_if_the_users_are_missing(): void
    {
        $this->app['env'] = 'local';

        $this->assertSame(0, $this->runSeeder('LocalTestDataSeeder'));

        $this->assertStringContainsString('LocalTestUsersSeeder', Artisan::output());
        $this->assertDataTablesAreEmpty();
    }

    public function test_data_seeder_creates_nothing_outside_local(): void
    {
        $this->app['env'] = 'local';
        $this->runSeeder('LocalTestUsersSeeder');

        foreach (['testing', 'production'] as $environment) {
            $this->app['env'] = $environment;

            $this->assertSame(0, $this->runSeeder('LocalTestDataSeeder'));

            $this->assertStringContainsString('solo se puede ejecutar en local', Artisan::output());
            $this->assertDataTablesAreEmpty();
        }
    }

    private function seedUsersAndData(): void
    {
        $this->app['env'] = 'local';

        $this->assertSame(0, $this->runSeeder('LocalTestUsersSeeder'));
        $this->assertSame(0, $this->runSeeder('LocalTestDataSeeder'));
    }

    private function runSeeder(string $class): int
    {
        return Artisan::call('db:seed', ['--class' => $class, '--force' => true]);
    }

    private function assertDataTablesAreEmpty(): void
    {
        foreach (['brands', 'categories', 'flavors', 'favorites', 'mixes', 'mix_items'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "The {$table} table should be empty.");
        }
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function seededRows(): array
    {
        $rows = [];

        foreach (['users', 'brands', 'categories', 'flavors', 'mixes', 'mix_items'] as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }

        $rows['favorites'] = DB::table('favorites')
            ->orderBy('user_id')
            ->orderBy('flavor_id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $rows;
    }
}
