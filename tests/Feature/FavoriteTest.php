<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Flavor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.locale' => 'en']);
        app()->setLocale('en');
    }

    private function favoriteExists(User $user, Flavor $flavor): bool
    {
        return Favorite::where('user_id', $user->id)->where('flavor_id', $flavor->id)->exists();
    }

    // RF-16
    public function test_it_adds_a_flavor_that_is_not_saved_and_shows_a_message(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('home'))
            ->followingRedirects()
            ->post(route('favorites.store', $flavor));

        $response->assertOk();
        $response->assertSee('Sabor añadido a favoritos.');
        $this->assertTrue($this->favoriteExists($user, $flavor));
    }

    public function test_it_removes_a_flavor_that_is_already_saved_and_shows_a_message(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $flavor->id]);

        $response = $this->actingAs($user)
            ->from(route('favorites.index'))
            ->followingRedirects()
            ->post(route('favorites.store', $flavor));

        $response->assertOk();
        $response->assertSee('Sabor eliminado de favoritos.');
        $this->assertFalse($this->favoriteExists($user, $flavor));
        $this->assertModelExists($flavor);
    }

    public function test_it_requires_a_session(): void
    {
        $flavor = Flavor::factory()->create();

        $this->post(route('favorites.store', $flavor))->assertRedirect(route('login'));
        $this->assertSame(0, Favorite::count());
    }

    // Spec 003, RF7: con una petición JSON, el corazón cambia el favorito sin redirigir.
    public function test_a_json_request_adds_the_favorite_and_responds_json(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', $flavor))
            ->assertOk()
            ->assertExactJson(['favorited' => true, 'message' => 'Sabor añadido a favoritos.']);

        $this->assertTrue($this->favoriteExists($user, $flavor));
    }

    public function test_a_json_request_removes_the_favorite_and_responds_json(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $flavor->id]);

        $this->actingAs($user)
            ->postJson(route('favorites.store', $flavor))
            ->assertOk()
            ->assertExactJson(['favorited' => false, 'message' => 'Sabor eliminado de favoritos.']);

        $this->assertFalse($this->favoriteExists($user, $flavor));
        $this->assertModelExists($flavor);
    }

    public function test_a_json_request_without_session_responds_401(): void
    {
        $flavor = Flavor::factory()->create();

        $this->postJson(route('favorites.store', $flavor))->assertUnauthorized();
        $this->assertSame(0, Favorite::count());
    }

    public function test_a_json_request_only_changes_the_favorites_of_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $other->id, 'flavor_id' => $flavor->id]);

        // El otro usuario lo tiene, pero para este usuario se añade (no se quita el ajeno).
        $this->actingAs($user)
            ->postJson(route('favorites.store', $flavor))
            ->assertOk()
            ->assertJson(['favorited' => true]);

        $this->assertTrue($this->favoriteExists($user, $flavor));
        $this->assertTrue($this->favoriteExists($other, $flavor));

        $this->postJson(route('favorites.store', $flavor))->assertJson(['favorited' => false]);

        $this->assertFalse($this->favoriteExists($user, $flavor));
        $this->assertTrue($this->favoriteExists($other, $flavor));
    }

    public function test_the_heart_uses_the_favorite_toggle_in_both_pages(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $flavor->id]);

        foreach ([route('home'), route('favorites.index')] as $url) {
            $content = $this->actingAs($user)->get($url)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<form[^>]*action="'.preg_quote(route('favorites.store', $flavor), '/').'"[^>]*data-favorite-toggle/', $content);
            $this->assertSame(1, substr_count($content, 'function initFavoriteToggles'));
            $this->assertSame(1, substr_count($content, 'id="favoriteToasts"'));
        }
    }

    // RF-17
    public function test_it_only_lists_the_favorites_of_the_user(): void
    {
        $user = User::factory()->create();
        $mine = Flavor::factory()->create(['name' => 'Mi favorito']);
        $theirs = Flavor::factory()->create(['name' => 'Favorito de otro']);
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $mine->id]);
        Favorite::create(['user_id' => User::factory()->create()->id, 'flavor_id' => $theirs->id]);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('Mi favorito');
        $response->assertDontSee('Favorito de otro');
    }

    public function test_removing_a_favorite_does_not_affect_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $flavor->id]);
        Favorite::create(['user_id' => $other->id, 'flavor_id' => $flavor->id]);

        $this->actingAs($user)->from(route('home'))->post(route('favorites.store', $flavor));

        $this->assertFalse($this->favoriteExists($user, $flavor));
        $this->assertTrue($this->favoriteExists($other, $flavor));
    }

    public function test_a_favorite_of_another_user_is_not_marked_in_the_catalog(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $other->id, 'flavor_id' => $flavor->id]);

        $this->actingAs($user)->get(route('home'))->assertDontSee('favorite-btn active');
        $this->actingAs($other)->get(route('home'))->assertSee('favorite-btn active');
    }

    // RF-18
    public function test_a_non_public_flavor_can_be_added_listed_and_removed(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->notPublic()->create(['name' => 'Sabor oculto']);

        $this->actingAs($user)->from(route('favorites.index'))
            ->post(route('favorites.store', $flavor))
            ->assertRedirect(route('favorites.index'));
        $this->assertTrue($this->favoriteExists($user, $flavor));

        $this->get(route('favorites.index'))->assertOk()->assertSee('Sabor oculto');
        $this->get(route('home'))->assertOk()->assertDontSee('Sabor oculto');

        $this->from(route('favorites.index'))->post(route('favorites.store', $flavor));
        $this->assertFalse($this->favoriteExists($user, $flavor));
    }

    // Filtros y orden

    private function favoriteFor(User $user, array $attributes, string $addedAt = '2026-01-01 10:00:00'): Flavor
    {
        $flavor = Flavor::factory()->create($attributes);
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $flavor->id]);
        Favorite::where('user_id', $user->id)->where('flavor_id', $flavor->id)->update(['created_at' => $addedAt]);

        return $flavor;
    }

    public function test_it_filters_by_brand_category_and_type(): void
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $this->favoriteFor($user, ['name' => 'De la marca', 'brand_id' => $brand->id]);
        $this->favoriteFor($user, ['name' => 'De la categoria', 'category_id' => $category->id]);
        $this->favoriteFor($user, ['name' => 'Del tipo', 'tobacco_type_id' => $this->tobaccoType('base')]);

        $this->actingAs($user);

        $this->get(route('favorites.index', ['brand_id' => $brand->id]))
            ->assertSee('De la marca')->assertDontSee('De la categoria')->assertDontSee('Del tipo');
        $this->get(route('favorites.index', ['category_id' => $category->id]))
            ->assertSee('De la categoria')->assertDontSee('De la marca')->assertDontSee('Del tipo');
        $this->get(route('favorites.index', ['tobacco_type_id' => $this->tobaccoType('base')]))
            ->assertSee('Del tipo')->assertDontSee('De la marca')->assertDontSee('De la categoria');
    }

    public function test_the_filters_combine_and_show_how_many_favorites_match(): void
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        $this->favoriteFor($user, ['name' => 'Cumple', 'brand_id' => $brand->id, 'tobacco_type_id' => $this->tobaccoType('rojo')]);
        $this->favoriteFor($user, ['name' => 'Otro tipo', 'brand_id' => $brand->id, 'tobacco_type_id' => $this->tobaccoType('negro')]);
        $this->favoriteFor($user, ['name' => 'Otra marca', 'tobacco_type_id' => $this->tobaccoType('rojo')]);

        $this->actingAs($user)->get(route('favorites.index', ['brand_id' => $brand->id, 'tobacco_type_id' => $this->tobaccoType('rojo')]))
            ->assertSee('Cumple')
            ->assertDontSee('Otro tipo')
            ->assertDontSee('Otra marca')
            ->assertSee('1 de 3 favoritos');
    }

    public function test_the_search_ignores_case_and_accents_in_name_and_ingredients(): void
    {
        $user = User::factory()->create();
        $this->favoriteFor($user, ['name' => 'Piña Tropical', 'ingredients_text' => 'Coco']);
        $this->favoriteFor($user, ['name' => 'Bosque', 'ingredients_text' => 'Arándano y hielo']);

        $this->actingAs($user);

        $this->get(route('favorites.index', ['search' => 'PINA']))->assertSee('Piña Tropical')->assertDontSee('Bosque');
        $this->get(route('favorites.index', ['search' => 'arandano']))->assertSee('Bosque')->assertDontSee('Piña Tropical');
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function sorts(): array
    {
        return [
            'nombre A-Z, sin indicar' => ['', ['Ámbar', 'Coco', 'Menta']],
            'nombre A-Z' => ['name_asc', ['Ámbar', 'Coco', 'Menta']],
            'nombre Z-A' => ['name_desc', ['Menta', 'Coco', 'Ámbar']],
            'marca' => ['brand', ['Coco', 'Menta', 'Ámbar']],
            'categoría, sin categoría al final' => ['category', ['Menta', 'Coco', 'Ámbar']],
            'tipo, sin tipo al final' => ['type', ['Coco', 'Ámbar', 'Menta']],
            'recientes' => ['recent', ['Coco', 'Ámbar', 'Menta']],
            'valor no válido' => ['drop table', ['Ámbar', 'Coco', 'Menta']],
        ];
    }

    #[DataProvider('sorts')]
    public function test_it_sorts_the_favorites(string $sort, array $expectedOrder): void
    {
        $user = User::factory()->create();
        $first = Brand::factory()->create(['name' => 'Al Fakher']);
        $last = Brand::factory()->create(['name' => 'Zomo']);
        $category = Category::factory()->create(['name' => 'Frutal']);
        $other = Category::factory()->create(['name' => 'Dulce']);

        $this->favoriteFor($user, ['name' => 'Menta', 'brand_id' => $first->id, 'category_id' => $other->id, 'tobacco_type_id' => null], '2026-01-01 10:00:00');
        $this->favoriteFor($user, ['name' => 'Ámbar', 'brand_id' => $last->id, 'category_id' => null, 'tobacco_type_id' => $this->tobaccoType('rubio')], '2026-01-02 10:00:00');
        $this->favoriteFor($user, ['name' => 'Coco', 'brand_id' => $first->id, 'category_id' => $category->id, 'tobacco_type_id' => $this->tobaccoType('negro')], '2026-01-03 10:00:00');

        $response = $this->actingAs($user)->get(route('favorites.index', ['sort' => $sort]))->assertOk();

        preg_match_all('/data-name="([^"]+)"/u', $response->getContent(), $names);
        $this->assertSame($expectedOrder, $names[1]);
    }

    public function test_sorting_by_a_field_groups_the_favorites_under_a_heading(): void
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create(['name' => 'Al Fakher']);
        $this->favoriteFor($user, ['name' => 'Menta', 'brand_id' => $brand->id, 'category_id' => null, 'tobacco_type_id' => $this->tobaccoType('sin_nicotina')]);

        $this->actingAs($user);

        $this->get(route('favorites.index', ['sort' => 'brand']))->assertSee('<h3 class="fav-group-title">Al Fakher</h3>', false);
        $this->get(route('favorites.index', ['sort' => 'type']))->assertSee('<h3 class="fav-group-title">Sin nicotina</h3>', false);
        $this->get(route('favorites.index', ['sort' => 'category']))->assertSee('<h3 class="fav-group-title">Sin categoría</h3>', false);
        $this->get(route('favorites.index'))->assertDontSee('<h3 class="fav-group-title"', false);
    }

    public function test_the_filters_only_offer_values_of_the_user_favorites_and_keep_the_selection(): void
    {
        $user = User::factory()->create();
        $mine = Brand::factory()->create(['name' => 'Marca mía']);
        Brand::factory()->create(['name' => 'Marca ajena']);
        $this->favoriteFor($user, ['brand_id' => $mine->id, 'tobacco_type_id' => $this->tobaccoType('base')]);
        Flavor::factory()->create(['tobacco_type_id' => $this->tobaccoType('rojo')]);

        $response = $this->actingAs($user)
            ->get(route('favorites.index', ['brand_id' => $mine->id, 'tobacco_type_id' => $this->tobaccoType('base'), 'sort' => 'brand', 'search' => 'x']))
            ->assertOk();

        $response->assertSee('Marca mía');
        $response->assertDontSee('Marca ajena');
        $response->assertSee('<option value="'.$mine->id.'" selected>', false);
        // Spec 012: las opciones de tipo son los ids de la tabla, con su nombre.
        $response->assertSee('<option value="'.$this->tobaccoType('base').'" selected>Base</option>', false);
        $response->assertDontSee('>Rojo</option>', false);
        $response->assertSee('<option value="brand" selected>', false);
        $response->assertSee('value="x"', false);
    }

    // Spec 003, RF2: los cuatro desplegables usan el dropdown y se siguen enviando al elegir.
    public function test_the_filters_use_the_filter_dropdown_and_submit_on_change(): void
    {
        $user = User::factory()->create();
        $this->favoriteFor($user, ['name' => 'Menta']);

        $content = $this->actingAs($user)->get(route('favorites.index'))->assertOk()->getContent();

        foreach (['brand_id', 'category_id', 'tobacco_type_id', 'sort'] as $field) {
            $this->assertMatchesRegularExpression(
                '/<select name="'.$field.'" id="'.$field.'"[^>]*onchange="this\.form\.submit\(\)"[^>]*data-filter-dropdown/',
                $content,
            );
        }
        $this->assertSame(1, substr_count($content, 'function initFilterDropdowns'));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidFilters(): array
    {
        return [
            'marca no numérica' => [['brand_id' => 'abc']],
            'marca como array' => [['brand_id' => ['1']]],
            'categoría negativa' => [['category_id' => '-1']],
            'tipo inexistente' => [['tobacco_type_id' => '999']],
            'tipo como array' => [['tobacco_type_id' => ['1']]],
            'búsqueda como array' => [['search' => ['Menta']]],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_an_invalid_filter_gives_no_results_without_error(array $query): void
    {
        $user = User::factory()->create();
        $this->favoriteFor($user, ['name' => 'Menta', 'tobacco_type_id' => $this->tobaccoType('rubio')]);

        $this->actingAs($user)->get(route('favorites.index', $query))
            ->assertOk()
            ->assertDontSee('data-name="Menta"', false)
            ->assertSee('Ningún favorito coincide');
    }

    public function test_an_invalid_sort_as_array_uses_the_default_order(): void
    {
        $user = User::factory()->create();
        $this->favoriteFor($user, ['name' => 'Menta']);

        $this->actingAs($user)->get(route('favorites.index', ['sort' => ['brand']]))
            ->assertOk()
            ->assertSee('data-name="Menta"', false);
    }

    public function test_a_user_without_favorites_is_sent_to_the_catalog_and_sees_no_filters(): void
    {
        $this->actingAs(User::factory()->create())->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('No tienes favoritos todavía')
            ->assertSee('href="'.route('home').'"', false)
            ->assertDontSee('<form class="fav-toolbar"', false);
    }

    public function test_removing_a_favorite_returns_to_the_filtered_list(): void
    {
        $user = User::factory()->create();
        $flavor = $this->favoriteFor($user, ['tobacco_type_id' => $this->tobaccoType('base')]);
        $filtered = route('favorites.index', ['tobacco_type_id' => $this->tobaccoType('base')]);

        $this->actingAs($user)->from($filtered)
            ->post(route('favorites.store', $flavor))
            ->assertRedirect($filtered);
    }

    // Spec 003, RF3 y RF4: cada favorito abre la misma ventana que el catálogo, en todos los grupos.
    public function test_each_favorite_has_its_detail_modal(): void
    {
        $user = User::factory()->create();
        $mint = $this->favoriteFor($user, [
            'name' => 'Menta Glacial',
            'description' => str_repeat('Frescor intenso de menta. ', 8).'Final de la descripción.',
            'ingredients_text' => 'Menta, hielo, lima y un toque de eucalipto para el final',
            'tobacco_type_id' => $this->tobaccoType('sin_nicotina'),
        ]);
        $grape = $this->favoriteFor($user, ['name' => 'Uva', 'tobacco_type_id' => $this->tobaccoType('negro')]);

        $content = $this->actingAs($user)
            ->get(route('favorites.index', ['sort' => 'type']))
            ->assertOk()
            ->getContent();

        $modal = $this->detailModal($content, $mint);
        $this->assertStringContainsString('Final de la descripción.', $modal);
        $this->assertStringContainsString('un toque de eucalipto para el final', $modal);
        $this->assertStringContainsString('Sin nicotina', $modal);
        $this->assertStringContainsString($mint->brand->name, $modal);
        // Spec 013: el enlace lleva el sabor.
        $this->assertStringContainsString('href="'.route('mixes.create', ['flavor' => $mint->id]).'"', $modal);
        $this->assertStringContainsString('Negro', $this->detailModal($content, $grape));

        $this->assertMatchesRegularExpression('/onclick="openFlavorModal\('.$mint->id.'\)"/', $content);
        $this->assertMatchesRegularExpression('/action="'.preg_quote(route('favorites.store', $mint), '/').'"[^>]*onclick="event\.stopPropagation\(\);"/', $content);
        $this->assertSame(1, substr_count($content, 'function openFlavorModal'));
        $this->assertStringNotContainsString('flavorModalOverlay', $content);
    }

    private function detailModal(string $content, Flavor $flavor): string
    {
        $start = strpos($content, 'id="flavorModal-'.$flavor->id.'"');
        $this->assertNotFalse($start, "Falta la ventana del sabor {$flavor->id}");
        $this->assertSame(1, substr_count($content, 'id="flavorModal-'.$flavor->id.'"'));

        $end = strpos($content, 'class="modal fade', $start);

        return $end === false ? substr($content, $start) : substr($content, $start, $end - $start);
    }

    // RF-19
    public function test_a_nonexistent_flavor_responds_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/favorites/999999')
            ->assertNotFound();

        $this->assertSame(0, Favorite::count());
    }
}
