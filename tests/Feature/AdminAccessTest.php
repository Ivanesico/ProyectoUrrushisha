<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Flavor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_ROUTE_NAMES = [
        'admin.index',
        'admin.brands.index', 'admin.brands.create', 'admin.brands.store', 'admin.brands.show',
        'admin.brands.edit', 'admin.brands.update', 'admin.brands.destroy',
        'admin.categories.index', 'admin.categories.create', 'admin.categories.store', 'admin.categories.show',
        'admin.categories.edit', 'admin.categories.update', 'admin.categories.destroy',
        'admin.flavors.index', 'admin.flavors.create', 'admin.flavors.store', 'admin.flavors.show',
        'admin.flavors.edit', 'admin.flavors.update', 'admin.flavors.destroy',
        // Spec 012: sección "Tipos de tabaco"
        'admin.tobacco-types.index', 'admin.tobacco-types.create', 'admin.tobacco-types.store', 'admin.tobacco-types.show',
        'admin.tobacco-types.edit', 'admin.tobacco-types.update', 'admin.tobacco-types.destroy',
    ];

    private Brand $brand;

    private Category $category;

    private Flavor $flavor;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $this->brand = Brand::factory()->create(['name' => 'Marca original']);
        $this->category = Category::factory()->create(['name' => 'Categoría original']);
        $this->flavor = Flavor::factory()->create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Sabor original',
        ]);
    }

    /**
     * Todas las combinaciones de método y URL de administración, leídas del router
     * para que una ruta nueva quede cubierta sin tocar el test.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function adminRequests(): array
    {
        $parameters = [
            'brand' => $this->brand->id,
            'category' => $this->category->id,
            'flavor' => $this->flavor->id,
            'tobacco_type' => $this->tobaccoType('herbal'),
        ];

        $requests = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (!str_starts_with((string) $route->getName(), 'admin.')) {
                continue;
            }

            $uri = route($route->getName(), array_intersect_key($parameters, array_flip($route->parameterNames())), false);

            foreach ($route->methods() as $method) {
                $requests[] = [$method, $uri];
            }
        }

        return $requests;
    }

    private function databaseSnapshot(): array
    {
        return [
            'brands' => DB::table('brands')->orderBy('id')->get()->toArray(),
            'categories' => DB::table('categories')->orderBy('id')->get()->toArray(),
            'flavors' => DB::table('flavors')->orderBy('id')->get()->toArray(),
            'tobacco_types' => DB::table('tobacco_types')->orderBy('id')->get()->toArray(),
        ];
    }

    private function validPayload(): array
    {
        return [
            'name' => 'Nombre nuevo',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ];
    }

    public function test_the_route_list_covers_every_admin_screen_and_action(): void
    {
        $names = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => (string) $route->getName())
            ->filter(fn (string $name) => str_starts_with($name, 'admin.'))
            ->sort()
            ->values()
            ->all();

        $expected = self::ADMIN_ROUTE_NAMES;
        sort($expected);

        $this->assertSame($expected, $names);
        // GET y HEAD en las rutas de lectura; PUT y PATCH en las de actualización
        // Spec 012: cuatro secciones (marcas, categorías, sabores y tipos de tabaco) más el panel.
        $this->assertCount(17 * 2 + 4 * 1 + 4 * 2 + 4 * 1, $this->adminRequests());
    }

    public function test_user_without_admin_permission_gets_403_on_every_route_and_method_without_changes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $before = $this->databaseSnapshot();

        foreach ($this->adminRequests() as [$method, $uri]) {
            $response = $this->actingAs($user)->call($method, $uri, $this->validPayload());

            $this->assertSame(403, $response->getStatusCode(), "{$method} {$uri}");
        }

        $this->assertEquals($before, $this->databaseSnapshot());
    }

    public function test_guest_is_redirected_to_login_on_every_route_and_method_without_changes(): void
    {
        $before = $this->databaseSnapshot();

        foreach ($this->adminRequests() as [$method, $uri]) {
            $response = $this->call($method, $uri, $this->validPayload());

            $this->assertSame(302, $response->getStatusCode(), "{$method} {$uri}");
            $this->assertSame(route('login'), $response->headers->get('Location'), "{$method} {$uri}");
        }

        $this->assertEquals($before, $this->databaseSnapshot());
        $this->assertGuest();
    }

    public function test_admin_can_open_every_admin_screen_with_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $screens = [
            [route('admin.index'), 'Panel de administración'],
            [route('admin.brands.index'), 'Marca original'],
            [route('admin.brands.create'), 'Añadir nueva marca'],
            [route('admin.brands.edit', $this->brand), 'Marca original'],
            [route('admin.categories.index'), 'Categoría original'],
            [route('admin.categories.create'), 'Añadir nueva categoría'],
            [route('admin.categories.edit', $this->category), 'Categoría original'],
            [route('admin.flavors.index'), 'Sabor original'],
            [route('admin.flavors.create'), 'Añadir nuevo sabor'],
            [route('admin.flavors.edit', $this->flavor), 'Sabor original'],
        ];

        foreach ($screens as [$url, $text]) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee($text);
        }
    }

    public function test_admin_can_open_every_list_with_no_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->flavor->delete();
        $this->brand->delete();
        $this->category->delete();

        $this->actingAs($admin)->get(route('admin.brands.index'))->assertOk()->assertSee('No hay marcas.');
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk()->assertSee('No hay categorías.');
        $this->actingAs($admin)->get(route('admin.flavors.index'))->assertOk()->assertSee('No hay sabores registrados.');
    }

    public function test_categories_list_links_each_category_to_its_own_actions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee(route('admin.categories.edit', $this->category), false)
            ->assertSee(route('admin.categories.destroy', $this->category), false);
    }

    public function test_detail_page_of_a_brand_category_or_flavor_in_admin_gives_404(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $urls = [
            route('admin.brands.show', $this->brand),
            route('admin.categories.show', $this->category),
            route('admin.flavors.show', $this->flavor),
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)
                ->assertNotFound()
                ->assertSee('Página no encontrada')
                ->assertDontSee('Not Found');
        }
    }
}
