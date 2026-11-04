<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Flavor;
use App\Models\Mix;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    private const FORBIDDEN_TEXT = 'No tienes permiso para acceder a esta página';

    private const NOT_FOUND_TEXT = 'Página no encontrada';

    protected function setUp(): void
    {
        parent::setUp();

        // RF-60: los textos en español no dependen del idioma de la aplicación.
        config(['app.locale' => 'en']);
        app()->setLocale('en');
    }

    private function assertSpanishNotFoundPage(TestResponse $response): void
    {
        $response->assertNotFound();
        $response->assertSee(self::NOT_FOUND_TEXT);
        $response->assertSee('URRUSHISHA');
        $response->assertDontSee('Not Found');
    }

    private function assertSpanishForbiddenPage(TestResponse $response): void
    {
        $response->assertForbidden();
        $response->assertSee(self::FORBIDDEN_TEXT);
        $response->assertSee('URRUSHISHA');
        $response->assertDontSee('Forbidden');
    }

    // RF-62: URL sin ninguna ruta
    public function test_an_unknown_url_shows_the_spanish_404_page_without_a_session(): void
    {
        $this->assertSpanishNotFoundPage($this->get('/esta-url-no-existe'));
    }

    public function test_an_unknown_url_shows_the_spanish_404_page_with_a_session(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertSpanishNotFoundPage($this->get('/esta-url-no-existe'));
    }

    // RF-62: ruta que existe, con un registro que no existe
    public function test_a_missing_record_shows_the_spanish_404_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertSpanishNotFoundPage($this->get('/mixes/999999/edit'));
    }

    // RF-62: 403 con sesión, por administración y por mezcla ajena
    public function test_a_non_admin_sees_the_spanish_403_page_in_administration(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $this->assertSpanishForbiddenPage($this->get(route('admin.index')));
    }

    public function test_another_users_mix_shows_the_spanish_403_page(): void
    {
        $mix = Mix::factory()->create();

        $this->actingAs(User::factory()->create());

        $this->assertSpanishForbiddenPage($this->get(route('mixes.edit', $mix)));
    }

    // RF-62: 403 sin sesión. Ninguna ruta real lo da, porque `auth` redirige antes.
    public function test_the_spanish_403_page_also_works_without_a_session(): void
    {
        Route::get('/prueba-403', fn () => abort(403));

        $this->assertSpanishForbiddenPage($this->get('/prueba-403'));
    }

    // RF-62: el caso contrario, una página normal no muestra los textos de error
    public function test_a_normal_page_does_not_show_the_error_texts(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee(self::FORBIDDEN_TEXT)
            ->assertDontSee(self::NOT_FOUND_TEXT);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function routesWithAnId(): array
    {
        return [
            'editar mezcla' => ['GET', '/mixes/%s/edit', false],
            'actualizar mezcla' => ['PUT', '/mixes/%s', false],
            'eliminar mezcla' => ['DELETE', '/mixes/%s', false],
            'favorito' => ['POST', '/favorites/%s', false],
            'marca pública' => ['GET', '/brands/%s', false],
            'editar marca' => ['GET', '/admin/brands/%s/edit', true],
            'editar categoría' => ['GET', '/admin/categories/%s/edit', true],
            'editar sabor' => ['GET', '/admin/flavors/%s/edit', true],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool, 3: string}>
     */
    public static function routesWithAnInvalidId(): array
    {
        $cases = [];

        foreach (self::routesWithAnId() as $name => $route) {
            foreach (['abc', '99999999999999999999', '0', '-1', '1.5'] as $id) {
                $cases["$name con $id"] = [...$route, $id];
            }
        }

        return $cases;
    }

    // RF-55 (URL): 404 y el valor no llega como parámetro a ninguna consulta
    #[DataProvider('routesWithAnInvalidId')]
    public function test_an_invalid_id_in_the_url_gives_404_and_never_reaches_a_query(string $method, string $uri, bool $admin, string $id): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => $admin]));

        $bindings = [];
        DB::listen(function ($query) use (&$bindings) {
            array_push($bindings, ...array_map('strval', $query->bindings));
        });

        $response = $this->call($method, sprintf($uri, $id));

        $this->assertSpanishNotFoundPage($response);
        $this->assertNotContains($id, $bindings);
    }

    // RF-55: el caso contrario, un id válido de hasta 18 dígitos sí llega a la consulta
    #[DataProvider('routesWithAnId')]
    public function test_a_valid_id_reaches_the_query(string $method, string $uri, bool $admin): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => $admin]));

        $id = '999999999999999999';

        $bindings = [];
        DB::listen(function ($query) use (&$bindings) {
            array_push($bindings, ...array_map('strval', $query->bindings));
        });

        $response = $this->call($method, sprintf($uri, $id));

        $this->assertSpanishNotFoundPage($response);
        $this->assertContains($id, $bindings);
    }

    // RF-55: el caso contrario, los registros que existen se siguen abriendo
    public function test_existing_records_still_open_with_the_id_patterns(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $mix = Mix::factory()->create(['user_id' => $user->id]);
        $brand = Brand::factory()->create();
        $flavor = Flavor::factory()->create(['brand_id' => $brand->id]);

        $this->actingAs($user);

        $this->get("/mixes/{$mix->id}/edit")->assertOk();
        $this->get("/admin/brands/{$brand->id}/edit")->assertOk();
        $this->get("/admin/categories/{$flavor->category_id}/edit")->assertOk();
        $this->get("/admin/flavors/{$flavor->id}/edit")->assertOk();

        $this->from(route('favorites.index'))
            ->post("/favorites/{$flavor->id}")
            ->assertRedirect(route('favorites.index'));
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'flavor_id' => $flavor->id]);
    }

    // RF-55 junto con RF-7: sin sesión, un id no válido no corresponde a ninguna ruta
    public function test_a_guest_with_an_invalid_id_gets_the_spanish_404_page(): void
    {
        $this->assertSpanishNotFoundPage($this->get('/mixes/abc/edit'));
        $this->assertGuest();
    }
}
