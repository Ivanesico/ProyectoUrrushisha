<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Flavor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminBrandCategoryTest extends TestCase
{
    use RefreshDatabase;

    private const ERROR_A_REASON = 'Error conocido A (excepción expresa de la spec 001): un array en un campo de texto provoca un error de servidor al volver al formulario.';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // RF-60: los mensajes no dependen del idioma de la aplicación
        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
    }

    /**
     * @return array<string, array{0: string, 1: class-string, 2: string, 3: int}>
     */
    public static function resources(): array
    {
        return [
            'marca' => ['brands', Brand::class, 'brand', 120],
            'categoría' => ['categories', Category::class, 'category', 80],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: class-string, 2: string, 3: array<string, string>}>
     */
    public static function nameMessages(): array
    {
        return [
            'marca' => ['brands', Brand::class, 'brand', [
                'required' => 'El nombre de la marca es obligatorio.',
                'string' => 'El nombre de la marca debe ser un texto.',
                'max' => 'El nombre de la marca no puede superar 120 caracteres.',
                'unique' => 'Esta marca ya existe.',
            ]],
            'categoría' => ['categories', Category::class, 'category', [
                'required' => 'El nombre de la categoría es obligatorio.',
                'string' => 'El nombre de la categoría debe ser un texto.',
                'max' => 'El nombre de la categoría no puede superar 80 caracteres.',
                'unique' => 'Esta categoría ya existe.',
            ]],
        ];
    }

    private function successMessage(string $resource, string $action): string
    {
        $label = $resource === 'brands' ? 'Marca' : 'Categoría';

        return "{$label} {$action}";
    }

    // RF-38

    #[DataProvider('resources')]
    public function test_admin_creates_a_record_and_sees_a_message(string $resource, string $model): void
    {
        $response = $this->followingRedirects()->post(route("admin.{$resource}.store"), ['name' => 'Nueva']);

        $response->assertOk()->assertSee($this->successMessage($resource, 'creada'));
        $this->assertDatabaseHas($resource, ['name' => 'Nueva']);
    }

    #[DataProvider('resources')]
    public function test_admin_updates_a_record_and_the_message_says_updated(string $resource, string $model): void
    {
        $record = $model::factory()->create(['name' => 'Antigua']);

        $response = $this->followingRedirects()->put(route("admin.{$resource}.update", $record), ['name' => 'Cambiada']);

        $response->assertOk()
            ->assertSee($this->successMessage($resource, 'actualizada'))
            ->assertDontSee($this->successMessage($resource, 'creada'));
        $this->assertSame('Cambiada', $record->fresh()->name);
    }

    #[DataProvider('resources')]
    public function test_update_redirect_carries_the_updated_message(string $resource, string $model): void
    {
        $record = $model::factory()->create(['name' => 'Antigua']);

        $this->put(route("admin.{$resource}.update", $record), ['name' => 'Cambiada'])
            ->assertRedirect(route("admin.{$resource}.index"))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'actualizada'));
    }

    #[DataProvider('resources')]
    public function test_admin_deletes_a_record_without_flavors_and_sees_a_message(string $resource, string $model): void
    {
        $record = $model::factory()->create();

        $response = $this->followingRedirects()->delete(route("admin.{$resource}.destroy", $record));

        $response->assertOk()->assertSee($this->successMessage($resource, 'eliminada'));
        $this->assertDatabaseMissing($resource, ['id' => $record->id]);
    }

    // RF-39

    #[DataProvider('resources')]
    public function test_saving_without_changing_the_name_is_accepted(string $resource, string $model): void
    {
        $record = $model::factory()->create(['name' => 'Misma']);

        $this->put(route("admin.{$resource}.update", $record), ['name' => 'Misma'])
            ->assertRedirect(route("admin.{$resource}.index"))
            ->assertSessionHasNoErrors();

        $this->assertSame('Misma', $record->fresh()->name);
    }

    #[DataProvider('resources')]
    public function test_updating_to_the_name_of_another_record_is_rejected(string $resource, string $model): void
    {
        $model::factory()->create(['name' => 'Ocupada']);
        $record = $model::factory()->create(['name' => 'Libre']);

        $this->put(route("admin.{$resource}.update", $record), ['name' => 'Ocupada'])->assertSessionHasErrors('name');

        $this->assertSame('Libre', $record->fresh()->name);
    }

    // RF-40 y RF-54

    #[DataProvider('nameMessages')]
    public function test_every_name_rule_has_its_spanish_message_when_creating(string $resource, string $model, string $param, array $messages): void
    {
        $model::factory()->create(['name' => 'Ocupada']);
        $tooLong = str_repeat('a', $resource === 'brands' ? 121 : 81);

        $cases = [
            'required' => '',
            'string' => ['x'],
            'max' => $tooLong,
            'unique' => 'Ocupada',
        ];

        foreach ($cases as $rule => $value) {
            $this->post(route("admin.{$resource}.store"), ['name' => $value])
                ->assertSessionHasErrors(['name' => $messages[$rule]]);
        }

        $this->assertSame(1, $model::count());
    }

    #[DataProvider('nameMessages')]
    public function test_every_name_rule_has_its_spanish_message_when_updating(string $resource, string $model, string $param, array $messages): void
    {
        $model::factory()->create(['name' => 'Ocupada']);
        $record = $model::factory()->create(['name' => 'Libre']);
        $tooLong = str_repeat('a', $resource === 'brands' ? 121 : 81);

        $cases = [
            'required' => '',
            'string' => ['x'],
            'max' => $tooLong,
            'unique' => 'Ocupada',
        ];

        foreach ($cases as $rule => $value) {
            $this->put(route("admin.{$resource}.update", $record), ['name' => $value])
                ->assertSessionHasErrors(['name' => $messages[$rule]]);
        }

        $this->assertSame('Libre', $record->fresh()->name);
    }

    #[DataProvider('resources')]
    public function test_a_name_at_the_limit_is_accepted(string $resource, string $model, string $param, int $limit): void
    {
        $name = str_repeat('a', $limit);

        $this->post(route("admin.{$resource}.store"), ['name' => $name])->assertSessionHasNoErrors();

        $this->assertDatabaseHas($resource, ['name' => $name]);
    }

    #[DataProvider('nameMessages')]
    public function test_the_error_is_shown_on_the_form(string $resource, string $model, string $param, array $messages): void
    {
        $response = $this->followingRedirects()
            ->from(route("admin.{$resource}.create"))
            ->post(route("admin.{$resource}.store"), ['name' => '']);

        $response->assertOk()->assertSee($messages['required'])->assertDontSee('validation.');
    }

    #[DataProvider('nameMessages')]
    public function test_an_array_as_name_does_not_break_the_form_when_creating(string $resource, string $model, string $param, array $messages): void
    {
        $this->markTestSkipped(self::ERROR_A_REASON);

        $response = $this->followingRedirects()
            ->from(route("admin.{$resource}.create"))
            ->post(route("admin.{$resource}.store"), ['name' => ['x']]);

        $response->assertOk()->assertSee($messages['string']);
    }

    #[DataProvider('nameMessages')]
    public function test_an_array_as_name_does_not_break_the_form_when_updating(string $resource, string $model, string $param, array $messages): void
    {
        $this->markTestSkipped(self::ERROR_A_REASON);

        $record = $model::factory()->create();

        $response = $this->followingRedirects()
            ->from(route("admin.{$resource}.edit", $record))
            ->put(route("admin.{$resource}.update", $record), ['name' => ['x']]);

        $response->assertOk()->assertSee($messages['string']);
    }

    // RF-41

    public function test_a_brand_with_flavors_is_not_deleted_and_the_reason_is_shown(): void
    {
        $brand = Brand::factory()->create(['name' => 'Con sabores']);
        $flavor = Flavor::factory()->create(['brand_id' => $brand->id]);
        Favorite::create(['user_id' => $this->admin->id, 'flavor_id' => $flavor->id]);

        $response = $this->followingRedirects()->delete(route('admin.brands.destroy', $brand));

        $response->assertOk()
            ->assertSee('No se puede eliminar la marca porque tiene sabores asociados.')
            ->assertSee('Con sabores');

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
        $this->assertDatabaseHas('flavors', ['id' => $flavor->id, 'brand_id' => $brand->id]);
        $this->assertDatabaseHas('favorites', ['user_id' => $this->admin->id, 'flavor_id' => $flavor->id]);
    }

    // RF-42

    public function test_deleting_a_category_keeps_its_flavors_without_category_and_visible_in_the_catalog(): void
    {
        $category = Category::factory()->create();
        $flavor = Flavor::factory()->create(['category_id' => $category->id, 'name' => 'Sigue visible']);

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('flavors', ['id' => $flavor->id, 'category_id' => null]);

        $this->get(route('home'))->assertOk()->assertSee('Sigue visible');
        $this->get(route('admin.flavors.index'))->assertOk()->assertSee('Sigue visible');
        $this->get(route('admin.flavors.edit', $flavor))->assertOk()->assertSee('Sigue visible');
    }

    // RF-55

    public function test_invalid_ids_in_admin_urls_give_404_and_do_not_reach_a_query(): void
    {
        $bindings = [];
        DB::listen(function ($query) use (&$bindings) {
            $bindings = array_merge($bindings, $query->bindings);
        });

        foreach (['brands', 'categories', 'flavors'] as $resource) {
            foreach (['abc', '99999999999999999999', '0', '-1', '1.5'] as $id) {
                $this->get("/admin/{$resource}/{$id}/edit")->assertNotFound();
                $this->put("/admin/{$resource}/{$id}", ['name' => 'x'])->assertNotFound();
                $this->delete("/admin/{$resource}/{$id}")->assertNotFound();
            }
        }

        $this->assertNotContains('abc', $bindings);
        $this->assertNotContains('99999999999999999999', $bindings);
    }

    public function test_a_valid_id_that_does_not_exist_gives_404(): void
    {
        $this->get(route('admin.brands.edit', ['brand' => 999999]))->assertNotFound();
        $this->get(route('admin.categories.edit', ['category' => 999999]))->assertNotFound();
        $this->delete(route('admin.brands.destroy', ['brand' => 999999]))->assertNotFound();
    }
}
