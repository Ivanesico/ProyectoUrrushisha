<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Flavor;
use App\Models\Mix;
use App\Models\MixItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // RF-60: los textos en español no dependen del idioma de la aplicación.
        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $this->actingAs(User::factory()->create());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function catalog(array $filters = []): TestResponse
    {
        return $this->get(route('home', $filters))->assertOk();
    }

    /**
     * @return array<int, string>
     */
    private function names(TestResponse $response): array
    {
        return $response->viewData('flavors')->pluck('name')->all();
    }

    // RF-10
    public function test_it_only_lists_public_flavors(): void
    {
        Flavor::factory()->create(['name' => 'Sabor visible']);
        Flavor::factory()->notPublic()->create(['name' => 'Sabor oculto']);

        $response = $this->catalog();

        $this->assertSame(['Sabor visible'], $this->names($response));
        $response->assertSee('Sabor visible');
        $response->assertDontSee('Sabor oculto');
    }

    public function test_it_orders_flavors_by_name(): void
    {
        foreach (['Coco', 'Arándano', 'Banana'] as $name) {
            Flavor::factory()->create(['name' => $name]);
        }

        $response = $this->catalog();

        $this->assertSame(['Arándano', 'Banana', 'Coco'], $this->names($response));
        $response->assertSeeInOrder(['Arándano', 'Banana', 'Coco']);
    }

    public function test_it_shows_twelve_flavors_per_page(): void
    {
        foreach (range(1, 13) as $number) {
            Flavor::factory()->create(['name' => sprintf('Sabor %02d', $number)]);
        }

        $firstPage = $this->catalog();
        $secondPage = $this->catalog(['page' => 2]);

        $this->assertCount(12, $this->names($firstPage));
        $this->assertSame(13, $firstPage->viewData('flavors')->total());
        $this->assertNotContains('Sabor 13', $this->names($firstPage));
        $this->assertSame(['Sabor 13'], $this->names($secondPage));
    }

    public function test_it_keeps_the_filters_when_changing_page(): void
    {
        foreach (range(1, 13) as $number) {
            Flavor::factory()->create([
                'name' => sprintf('Menta %02d', $number),
                'tobacco_type' => 'negro',
            ]);
        }
        Flavor::factory()->create(['name' => 'Coco', 'tobacco_type' => 'rubio']);

        $firstPage = $this->catalog(['name' => 'menta', 'tobacco_type' => 'negro']);
        $firstPage->assertSee('?name=menta&tobacco_type=negro&page=2');

        $secondPage = $this->catalog(['name' => 'menta', 'tobacco_type' => 'negro', 'page' => 2]);
        $this->assertSame(['Menta 13'], $this->names($secondPage));
        $secondPage->assertSee('?name=menta&tobacco_type=negro&page=1');
    }

    // RF-11
    public function test_it_filters_by_brand(): void
    {
        $brand = Brand::factory()->create();
        Flavor::factory()->create(['name' => 'De la marca', 'brand_id' => $brand->id]);
        Flavor::factory()->create(['name' => 'De otra marca']);

        $this->assertSame(['De la marca'], $this->names($this->catalog(['brand_id' => $brand->id])));
    }

    public function test_it_filters_by_category(): void
    {
        $category = Category::factory()->create();
        Flavor::factory()->create(['name' => 'De la categoría', 'category_id' => $category->id]);
        Flavor::factory()->create(['name' => 'De otra categoría']);

        $this->assertSame(['De la categoría'], $this->names($this->catalog(['category_id' => $category->id])));
    }

    public function test_text_filters_accept_partial_matches(): void
    {
        Flavor::factory()->create([
            'name' => 'Blue Mist',
            'ingredients_text' => 'Menta y limón',
            'tobacco_type' => 'sin_nicotina',
        ]);
        Flavor::factory()->create([
            'name' => 'Coco',
            'ingredients_text' => 'Coco',
            'tobacco_type' => 'rubio',
        ]);

        $this->assertSame(['Blue Mist'], $this->names($this->catalog(['name' => 'ue mi'])));
        $this->assertSame(['Blue Mist'], $this->names($this->catalog(['ingredient' => 'enta'])));
        $this->assertSame(['Blue Mist'], $this->names($this->catalog(['tobacco_type' => 'nicotina'])));
    }

    public function test_it_returns_only_the_flavors_that_match_all_the_filters(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $matching = [
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'ingredients_text' => 'Menta',
            'tobacco_type' => 'negro',
        ];

        Flavor::factory()->create(['name' => 'Cumple todo'] + $matching);
        Flavor::factory()->create(['name' => 'Otra cosa'] + $matching);
        Flavor::factory()->create(array_merge($matching, ['name' => 'Cumple marca distinta', 'brand_id' => Brand::factory()->create()->id]));
        Flavor::factory()->create(array_merge($matching, ['name' => 'Cumple categoría distinta', 'category_id' => Category::factory()->create()->id]));
        Flavor::factory()->create(array_merge($matching, ['name' => 'Cumple ingrediente distinto', 'ingredients_text' => 'Coco']));
        Flavor::factory()->create(array_merge($matching, ['name' => 'Cumple tipo distinto', 'tobacco_type' => 'rubio']));

        $response = $this->catalog([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'cumple',
            'ingredient' => 'menta',
            'tobacco_type' => 'negro',
        ]);

        $this->assertSame(['Cumple todo'], $this->names($response));
    }

    public function test_empty_filters_do_not_filter(): void
    {
        Flavor::factory()->count(2)->create();

        $response = $this->catalog([
            'brand_id' => '',
            'category_id' => '',
            'name' => '',
            'ingredient' => '',
            'tobacco_type' => '',
        ]);

        $this->assertCount(2, $this->names($response));
    }

    public function test_a_tobacco_type_outside_the_list_gives_no_results(): void
    {
        Flavor::factory()->create();

        $this->assertSame([], $this->names($this->catalog(['tobacco_type' => 'Virginia'])));
    }

    public function test_a_flavor_without_category_is_still_listed(): void
    {
        Flavor::factory()->create(['name' => 'Sin categoría asignada', 'category_id' => null]);

        $this->assertSame(['Sin categoría asignada'], $this->names($this->catalog()));
    }

    public function test_the_form_keeps_the_applied_filters(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $response = $this->catalog([
            'name' => 'Piña',
            'ingredient' => 'Arándano',
            'tobacco_type' => 'rojo',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        $response->assertSee('value="Piña"', false);
        $response->assertSee('value="Arándano"', false);
        $response->assertSee('value="rojo"', false);
        $response->assertSee('<option value="'.$brand->id.'" selected>', false);
        $response->assertSee('<option value="'.$category->id.'" selected>', false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function accentInsensitiveSearches(): array
    {
        return [
            'arandano' => ['Arándano', 'arandano'],
            'ARANDANO' => ['Arándano', 'ARANDANO'],
            'Arándano' => ['Arándano', 'Arándano'],
            'ÁRÁNDANO' => ['Arándano', 'ÁRÁNDANO'],
            'pina' => ['Piña', 'pina'],
            'PIÑA' => ['Piña', 'PIÑA'],
            'piña' => ['Piña', 'piña'],
            'PINA' => ['Piña', 'PINA'],
            'guardado en mayúsculas' => ['ÁRÁNDANO', 'arandano'],
            'Ñ guardada en mayúscula' => ['PIÑA', 'pina'],
            'guardado sin acento' => ['Arandano', 'ÁRÁNDANO'],
            'n guardada sin tilde' => ['Pina', 'piña'],
            'parcial' => ['Arándano', 'RÁND'],
            'ü' => ['Güisqui', 'guisqui'],
        ];
    }

    // RF-12
    #[DataProvider('accentInsensitiveSearches')]
    public function test_the_name_filter_ignores_case_and_accents(string $stored, string $search): void
    {
        Flavor::factory()->create(['name' => $stored]);
        Flavor::factory()->create(['name' => 'Coco']);

        $this->assertSame([$stored], $this->names($this->catalog(['name' => $search])));
    }

    #[DataProvider('accentInsensitiveSearches')]
    public function test_the_ingredient_filter_ignores_case_and_accents(string $stored, string $search): void
    {
        Flavor::factory()->create(['name' => 'Con ingrediente', 'ingredients_text' => "Hielo, $stored"]);
        Flavor::factory()->create(['name' => 'Otro', 'ingredients_text' => 'Coco']);

        $this->assertSame(['Con ingrediente'], $this->names($this->catalog(['ingredient' => $search])));
    }

    /**
     * La columna solo admite la lista de tipos, que no tiene acentos ni ñ: lo que varía aquí
     * es lo que escribe el usuario.
     *
     * @return array<string, array{string, string}>
     */
    public static function accentInsensitiveTypeSearches(): array
    {
        return [
            'minúsculas' => ['herbal', 'herbal'],
            'mayúsculas' => ['herbal', 'HERBAL'],
            'acento' => ['herbal', 'hérbal'],
            'mayúscula acentuada' => ['herbal', 'HÉRBAL'],
            'rojo con acento' => ['rojo', 'RÓJO'],
            'ñ en minúscula' => ['sin_nicotina', 'sin_ñicotina'],
            'Ñ en mayúscula' => ['sin_nicotina', 'SIN_ÑICOTINA'],
        ];
    }

    #[DataProvider('accentInsensitiveTypeSearches')]
    public function test_the_tobacco_type_filter_ignores_case_and_accents(string $stored, string $search): void
    {
        Flavor::factory()->create(['name' => 'Con el tipo', 'tobacco_type' => $stored]);
        Flavor::factory()->create(['name' => 'Otro', 'tobacco_type' => 'rubio']);

        $this->assertSame(['Con el tipo'], $this->names($this->catalog(['tobacco_type' => $search])));
    }

    public function test_the_search_covers_all_the_letters_of_the_map(): void
    {
        Flavor::factory()->create(['name' => 'áéíóúüñÁÉÍÓÚÜÑ']);

        $response = $this->catalog(['name' => 'aeiouunaeiouun']);

        $this->assertSame(['áéíóúüñÁÉÍÓÚÜÑ'], $this->names($response));
    }

    public function test_the_search_term_is_normalized(): void
    {
        $this->assertSame('aeiouunaeiouun', Flavor::normalizeSearchTerm('áéíóúüñÁÉÍÓÚÜÑ'));
        $this->assertSame('blue mist', Flavor::normalizeSearchTerm('BLUE Mist'));
    }

    public function test_the_search_only_accepts_the_fixed_list_of_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Flavor::query()->matchingText('description', 'menta');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIds(): array
    {
        return [
            'texto' => ['abc'],
            'desbordamiento' => ['99999999999999999999'],
            'cero' => ['0'],
            'negativo' => ['-1'],
            'decimal' => ['1.5'],
            'número seguido de texto' => ['1abc'],
        ];
    }

    // RF-12b
    #[DataProvider('invalidIds')]
    public function test_an_invalid_brand_gives_no_results_and_does_not_reach_the_query(string $id): void
    {
        $this->assertInvalidIdFilter('brand_id', $id);
    }

    #[DataProvider('invalidIds')]
    public function test_an_invalid_category_gives_no_results_and_does_not_reach_the_query(string $id): void
    {
        $this->assertInvalidIdFilter('category_id', $id);
    }

    private function assertInvalidIdFilter(string $filter, string $id): void
    {
        Flavor::factory()->create();

        $bindings = [];
        DB::listen(function ($query) use (&$bindings) {
            array_push($bindings, ...$query->bindings);
        });

        $response = $this->catalog([$filter => $id]);

        $this->assertSame([], $this->names($response));
        $this->assertNotContains($id, array_map('strval', $bindings));
    }

    public function test_an_array_as_brand_or_category_gives_no_results(): void
    {
        $flavor = Flavor::factory()->create();

        $this->assertSame([], $this->names($this->catalog(['brand_id' => [$flavor->brand_id]])));
        $this->assertSame([], $this->names($this->catalog(['category_id' => [$flavor->category_id]])));
    }

    public function test_a_nonexistent_brand_gives_no_results(): void
    {
        Flavor::factory()->create();

        $this->assertSame([], $this->names($this->catalog(['brand_id' => '9223372036854775807'])));
    }

    public function test_a_valid_id_reaches_the_query_as_an_integer(): void
    {
        $flavor = Flavor::factory()->create();

        $bindings = [];
        DB::listen(function ($query) use (&$bindings) {
            if (str_contains($query->sql, '"brand_id" = ?')) {
                $bindings = $query->bindings;
            }
        });

        $this->catalog(['brand_id' => (string) $flavor->brand_id]);

        $this->assertContains($flavor->brand_id, $bindings);
        $this->assertNotContains((string) $flavor->brand_id, $bindings);
    }

    // RF-12c
    public function test_the_tobacco_type_filter_does_not_suggest_values_outside_the_list(): void
    {
        $response = $this->catalog();

        $response->assertDontSee('Virginia');
        $this->assertMatchesRegularExpression(
            '/id="tobacco_type"[^>]*placeholder="Ej\. (rubio|negro|herbal|rojo|sin_nicotina)"/',
            $response->getContent(),
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function initials(): array
    {
        return [
            'a acentuada' => ['Árbol', 'Á'],
            'e acentuada' => ['Éxtasis', 'É'],
            'eñe' => ['Ñandú', 'Ñ'],
            'minúscula acentuada' => ['ñoño', 'Ñ'],
            'sin acento' => ['menta', 'M'],
        ];
    }

    // RF-12d
    #[DataProvider('initials')]
    public function test_the_catalog_shows_the_initial_correctly(string $name, string $initial): void
    {
        Flavor::factory()->create(['name' => $name]);

        $this->assertInitial($this->catalog(), 'placeholder-text', $initial);
    }

    #[DataProvider('initials')]
    public function test_favorites_show_the_initial_correctly(string $name, string $initial): void
    {
        $flavor = Flavor::factory()->create(['name' => $name]);
        Favorite::create(['user_id' => auth()->id(), 'flavor_id' => $flavor->id]);

        $this->assertInitial($this->get(route('favorites.index'))->assertOk(), 'placeholder-text', $initial);
    }

    #[DataProvider('initials')]
    public function test_mixes_show_the_initial_correctly(string $name, string $initial): void
    {
        $flavor = Flavor::factory()->create(['name' => $name]);
        $mix = Mix::factory()->create(['user_id' => auth()->id()]);
        MixItem::create(['mix_id' => $mix->id, 'flavor_id' => $flavor->id, 'ratio' => 100]);

        $this->assertInitial($this->get(route('mixes.index'))->assertOk(), 'mix-flavor-placeholder', $initial);
    }

    private function assertInitial(TestResponse $response, string $class, string $initial): void
    {
        $this->assertMatchesRegularExpression(
            '/<(span|div) class="'.$class.'">\s*'.preg_quote($initial, '/').'\s*<\/(span|div)>/u',
            $response->getContent(),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function textFilters(): array
    {
        return [
            'nombre' => ['name'],
            'ingrediente' => ['ingredient'],
            'tipo' => ['tobacco_type'],
        ];
    }

    // RF-12e
    #[DataProvider('textFilters')]
    public function test_a_text_filter_sent_as_an_array_gives_no_results_and_an_empty_field(string $filter): void
    {
        Flavor::factory()->create();

        $response = $this->get('/?'.$filter.'[]=x');

        $response->assertOk();
        $this->assertSame([], $this->names($response));
        $this->assertSame('', $response->viewData('filters')[$filter]);
        $this->assertMatchesRegularExpression(
            '/id="'.$filter.'"[^>]*value=""/',
            $response->getContent(),
        );
    }

    public function test_an_id_filter_sent_as_an_array_selects_no_option(): void
    {
        $flavor = Flavor::factory()->create();

        $response = $this->get('/?brand_id[]='.$flavor->brand_id.'&category_id[]='.$flavor->category_id);

        $response->assertOk();
        $this->assertSame('', $response->viewData('filters')['brand_id']);
        $this->assertSame('', $response->viewData('filters')['category_id']);
        $response->assertDontSee(' selected>', false);
    }

    // RF-61
    public function test_the_catalog_create_mix_link_goes_to_the_creation_form(): void
    {
        $this->assertCreateMixLink($this->catalog());
    }

    public function test_the_favorites_create_mix_link_goes_to_the_creation_form(): void
    {
        $this->assertCreateMixLink($this->get(route('favorites.index'))->assertOk());
    }

    private function assertCreateMixLink(TestResponse $response): void
    {
        $linkTo = fn (string $url): string => '#<a href="'.preg_quote($url, '#').'"[^>]*>\s*Crear mezcla\s*</a>#';

        $this->assertMatchesRegularExpression($linkTo(route('mixes.create')), $response->getContent());
        $this->assertDoesNotMatchRegularExpression($linkTo(route('mixes.index')), $response->getContent());
    }

    // RF-63 y RF-60
    public function test_the_pagination_is_in_spanish_on_the_first_page(): void
    {
        Flavor::factory()->count(13)->create();

        $response = $this->catalog();

        $response->assertSeeText('Mostrando 1 a 12 de 13 resultados');
        $response->assertSee('Anterior');
        $response->assertSee('Siguiente');
        $response->assertSee('aria-label="Anterior"', false);
        $response->assertSee('aria-label="Siguiente"', false);
        $this->assertNoEnglishPagination($response);
    }

    public function test_the_pagination_is_in_spanish_on_the_last_page(): void
    {
        Flavor::factory()->count(13)->create();

        $response = $this->catalog(['page' => 2]);

        $response->assertSeeText('Mostrando 13 a 13 de 13 resultados');
        $response->assertSee('aria-label="Anterior"', false);
        $response->assertSee('aria-label="Siguiente"', false);
        $this->assertNoEnglishPagination($response);
    }

    public function test_there_is_no_pagination_with_a_single_page(): void
    {
        Flavor::factory()->count(12)->create();

        $response = $this->catalog();

        $response->assertDontSee('Mostrando');
        $response->assertDontSee('Siguiente');
    }

    private function assertNoEnglishPagination(TestResponse $response): void
    {
        foreach (['Showing', 'Previous', 'Next', 'pagination.previous', 'pagination.next'] as $text) {
            $response->assertDontSee($text, false);
        }
    }
}
