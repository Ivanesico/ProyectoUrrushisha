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

        $response = $this->catalog(['sort' => 'name_asc']);

        $this->assertSame(['Arándano', 'Banana', 'Coco'], $this->names($response));
        $response->assertSeeInOrder(['Arándano', 'Banana', 'Coco']);
    }

    public function test_it_shows_twelve_flavors_per_page(): void
    {
        foreach (range(1, 13) as $number) {
            // El orden por defecto es el de alta: el sabor 13 es el más antiguo y queda el último.
            Flavor::factory()->create([
                'name' => sprintf('Sabor %02d', $number),
                'created_at' => now()->subMinutes($number),
            ]);
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
                'created_at' => now()->subMinutes($number),
            ]);
        }
        Flavor::factory()->create(['name' => 'Coco', 'tobacco_type' => 'rubio']);

        $firstPage = $this->catalog(['name' => 'menta', 'tobacco_type' => 'negro']);
        $firstPage->assertSee('?name=menta&tobacco_type=negro&page=2');

        $secondPage = $this->catalog(['name' => 'menta', 'tobacco_type' => 'negro', 'page' => 2]);
        $this->assertSame(['Menta 13'], $this->names($secondPage));
        $secondPage->assertSee('?name=menta&tobacco_type=negro&page=1');
    }

    // Spec 004, RF2: orden en SQL, antes de paginar, con desempate final por id.
    public function test_the_default_order_is_recently_added_with_nulls_last(): void
    {
        Flavor::factory()->create(['name' => 'Antiguo', 'created_at' => now()->subDays(2)]);
        $sameMoment = now()->subDay();
        Flavor::factory()->create(['name' => 'Mismo momento A', 'created_at' => $sameMoment]);
        Flavor::factory()->create(['name' => 'Mismo momento B', 'created_at' => $sameMoment]);
        Flavor::factory()->create(['name' => 'Nuevo', 'created_at' => now()]);
        $withoutDate = [
            Flavor::factory()->create(['name' => 'Sin fecha A'])->id,
            Flavor::factory()->create(['name' => 'Sin fecha B'])->id,
        ];
        Flavor::whereIn('id', $withoutDate)->update(['created_at' => null]);

        $expected = ['Nuevo', 'Mismo momento B', 'Mismo momento A', 'Antiguo', 'Sin fecha B', 'Sin fecha A'];

        $default = $this->catalog();
        $this->assertSame($expected, $this->names($default));
        $this->assertSame('recent', $default->viewData('sort'));
        $this->assertSame($expected, $this->names($this->catalog(['sort' => 'recent'])));
    }

    public function test_it_orders_by_name_ignoring_case_and_accents(): void
    {
        foreach (['Coco', 'árbol', 'banana', 'ÁNGEL', 'Ñame', 'nube'] as $name) {
            Flavor::factory()->create(['name' => $name]);
        }

        $ascending = ['ÁNGEL', 'árbol', 'banana', 'Coco', 'Ñame', 'nube'];

        $this->assertSame($ascending, $this->names($this->catalog(['sort' => 'name_asc'])));
        $this->assertSame(array_reverse($ascending), $this->names($this->catalog(['sort' => 'name_desc'])));
    }

    public function test_equal_names_are_ordered_by_id(): void
    {
        $first = Flavor::factory()->create(['name' => 'MENTA']);
        $second = Flavor::factory()->create(['name' => 'Menta']);

        $ids = fn (TestResponse $response): array => $response->viewData('flavors')->pluck('id')->all();

        $this->assertSame([$first->id, $second->id], $ids($this->catalog(['sort' => 'name_asc'])));
        $this->assertSame([$first->id, $second->id], $ids($this->catalog(['sort' => 'name_desc'])));
    }

    public function test_it_orders_by_brand_and_then_by_name(): void
    {
        $zeta = Brand::factory()->create(['name' => 'Zeta']);
        $alfa = Brand::factory()->create(['name' => 'ÁLFA']);
        $beta = Brand::factory()->create(['name' => 'beta']);

        Flavor::factory()->create(['name' => 'Coco', 'brand_id' => $zeta->id]);
        Flavor::factory()->create(['name' => 'menta', 'brand_id' => $alfa->id]);
        Flavor::factory()->create(['name' => 'Árbol', 'brand_id' => $alfa->id]);
        Flavor::factory()->create(['name' => 'Uva', 'brand_id' => $beta->id]);

        $this->assertSame(['Árbol', 'menta', 'Uva', 'Coco'], $this->names($this->catalog(['sort' => 'brand'])));
    }

    public function test_it_orders_by_category_with_the_flavors_without_category_last(): void
    {
        $zeta = Category::factory()->create(['name' => 'Zeta']);
        $acido = Category::factory()->create(['name' => 'Ácido']);

        Flavor::factory()->create(['name' => 'Aaa sin categoría', 'category_id' => null]);
        Flavor::factory()->create(['name' => 'Coco', 'category_id' => $zeta->id]);
        Flavor::factory()->create(['name' => 'Menta', 'category_id' => $acido->id]);
        Flavor::factory()->create(['name' => 'banana', 'category_id' => $acido->id]);

        $this->assertSame(
            ['banana', 'Menta', 'Coco', 'Aaa sin categoría'],
            $this->names($this->catalog(['sort' => 'category'])),
        );
    }

    public function test_the_order_is_applied_together_with_the_filters(): void
    {
        foreach (['Menta azul', 'Coco', 'Menta fresca'] as $name) {
            Flavor::factory()->create(['name' => $name]);
        }

        $this->assertSame(
            ['Menta fresca', 'Menta azul'],
            $this->names($this->catalog(['name' => 'menta', 'sort' => 'name_desc'])),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sorts(): array
    {
        return [
            'recientes' => ['recent'],
            'nombre A-Z' => ['name_asc'],
            'nombre Z-A' => ['name_desc'],
            'marca' => ['brand'],
            'categoría' => ['category'],
        ];
    }

    #[DataProvider('sorts')]
    public function test_going_through_the_pages_shows_each_flavor_once(string $sort): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $created = now()->subDay();
        $ids = [];

        foreach (range(1, 20) as $number) {
            $ids[] = Flavor::factory()->create([
                'name' => 'Menta',
                'brand_id' => $brand->id,
                'category_id' => $number % 2 ? $category->id : null,
                'created_at' => $created,
            ])->id;
        }

        $seen = [];
        foreach ([1, 2] as $page) {
            array_push($seen, ...$this->catalog(['sort' => $sort, 'page' => $page])->viewData('flavors')->pluck('id')->all());
        }

        $this->assertCount(20, $seen);
        $this->assertEqualsCanonicalizing($ids, $seen);
    }

    // Spec 004, RF5: un orden fuera de la lista blanca usa el orden por defecto y no llega a la consulta.
    public function test_an_unknown_or_array_sort_uses_the_default_order(): void
    {
        Flavor::factory()->create(['name' => 'Antiguo', 'created_at' => now()->subDay()]);
        Flavor::factory()->create(['name' => 'Nuevo', 'created_at' => now()]);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql.' '.implode(' ', array_map('strval', $query->bindings));
        });

        foreach (['/?sort=name%3B%20drop%20table%20flavors', '/?sort=NAME_ASC', '/?sort[]=name_asc', '/?sort[name_asc]=1'] as $url) {
            $response = $this->get($url);

            $response->assertOk();
            $this->assertSame(['Nuevo', 'Antiguo'], $this->names($response));
            $this->assertSame('recent', $response->viewData('sort'));
        }

        foreach ($queries as $sql) {
            $this->assertStringNotContainsString('drop table', $sql);
        }
    }

    // Spec 005, RF2: los órdenes por ID son solo del admin; la portada ni los ofrece ni los acepta.
    public function test_the_catalog_ignores_the_id_sorts_of_the_admin(): void
    {
        Flavor::factory()->create(['name' => 'Antiguo', 'created_at' => now()->subDay()]);
        Flavor::factory()->create(['name' => 'Nuevo', 'created_at' => now()]);

        foreach (['id_asc', 'id_desc'] as $sort) {
            $response = $this->catalog(['sort' => $sort]);

            $this->assertSame(['Nuevo', 'Antiguo'], $this->names($response));
            $this->assertSame('recent', $response->viewData('sort'));
            $this->assertArrayNotHasKey($sort, $response->viewData('sorts'));
            $response->assertDontSee('value="'.$sort.'"', false);
        }
    }

    private function sortForm(string $content): string
    {
        $this->assertSame(1, preg_match('#<form[^>]*class="[^"]*sort-form[^"]*"[^>]*>.*?</form>#s', $content, $match), 'Falta el formulario de orden');

        return $match[0];
    }

    private function filtersForm(string $content): string
    {
        $this->assertSame(1, preg_match('#<form[^>]*class="[^"]*filters-form[^"]*"[^>]*>.*?</form>#s', $content, $match), 'Falta el formulario de filtros');

        return $match[0];
    }

    private function clearLink(string $content): string
    {
        $this->assertSame(1, preg_match('#<a href="([^"]*)"[^>]*>\s*Limpiar\s*</a>#', $content, $match), 'Falta el enlace Limpiar');

        return html_entity_decode($match[1]);
    }

    // Spec 004, RF1 y RF6: selector junto al título, con el dropdown y "Aplicar" solo sin JS.
    public function test_the_sort_selector_lists_the_options_and_marks_the_current_one(): void
    {
        $content = $this->catalog(['sort' => 'brand'])->getContent();
        $form = $this->sortForm($content);

        $this->assertStringContainsString('action="'.route('home').'#catalogo"', $form);
        $this->assertStringContainsString('method="GET"', $form);
        $this->assertMatchesRegularExpression('/<label for="sort"[^>]*>\s*Ordenar por:\s*<\/label>/', $form);
        $this->assertMatchesRegularExpression('/<select name="sort" id="sort"[^>]*onchange="this\.form\.submit\(\)"[^>]*data-filter-dropdown/', $form);
        $this->assertSame(1, substr_count($content, 'id="sort"'));

        preg_match_all('/<option value="(\w+)"( selected)?>\s*([^<]+?)\s*<\/option>/', $form, $options, PREG_SET_ORDER);
        $this->assertSame(
            ['recent' => 'Añadidos recientemente', 'name_asc' => 'Nombre (A-Z)', 'name_desc' => 'Nombre (Z-A)', 'brand' => 'Marca', 'category' => 'Categoría'],
            collect($options)->mapWithKeys(fn ($option) => [$option[1] => $option[3]])->all(),
        );
        $this->assertSame(['brand'], collect($options)->filter(fn ($option) => $option[2] !== '')->pluck(1)->values()->all());

        // El botón "Aplicar" solo existe sin JS.
        $this->assertMatchesRegularExpression('#<noscript>\s*<button type="submit"[^>]*>\s*Aplicar\s*</button>\s*</noscript>#', $form);
        $this->assertSame(1, substr_count($form, '<button'));
    }

    public function test_the_sort_selector_marks_recent_by_default(): void
    {
        $form = $this->sortForm($this->catalog()->getContent());

        $this->assertStringContainsString('<option value="recent" selected>', $form);
        $this->assertSame(1, substr_count($form, ' selected>'));
    }

    // Spec 004, RF4: el formulario de orden lleva la búsqueda y los filtros activos, sin page.
    public function test_the_sort_form_keeps_the_active_search_and_filters_without_page(): void
    {
        $brand = Brand::factory()->create();
        $form = $this->sortForm($this->catalog([
            'name' => 'Piña "dulce"',
            'brand_id' => $brand->id,
            'category_id' => '',
            'tobacco_type' => 'rojo',
            'page' => 2,
        ])->getContent());

        $this->assertStringContainsString('<input type="hidden" name="name" value="Piña &quot;dulce&quot;">', $form);
        $this->assertStringContainsString('<input type="hidden" name="brand_id" value="'.$brand->id.'">', $form);
        $this->assertStringContainsString('<input type="hidden" name="tobacco_type" value="rojo">', $form);
        $this->assertStringNotContainsString('name="category_id"', $form);
        $this->assertStringNotContainsString('name="ingredient"', $form);
        $this->assertStringNotContainsString('name="page"', $form);
        $this->assertSame(3, substr_count($form, 'type="hidden"'));
    }

    public function test_the_sort_form_has_no_hidden_fields_without_filters(): void
    {
        $this->assertStringNotContainsString('type="hidden"', $this->sortForm($this->catalog()->getContent()));
    }

    public function test_the_filters_form_keeps_the_sort(): void
    {
        $form = $this->filtersForm($this->catalog(['sort' => 'name_desc'])->getContent());
        $this->assertStringContainsString('<input type="hidden" name="sort" value="name_desc">', $form);

        $form = $this->filtersForm($this->catalog(['sort' => 'no-existe'])->getContent());
        $this->assertStringContainsString('<input type="hidden" name="sort" value="recent">', $form);
    }

    public function test_the_pagination_keeps_the_sort_and_the_filters(): void
    {
        foreach (range(1, 13) as $number) {
            Flavor::factory()->create(['name' => sprintf('Menta %02d', $number)]);
        }

        $firstPage = $this->catalog(['name' => 'menta', 'sort' => 'name_desc']);
        $firstPage->assertSee('?name=menta&sort=name_desc&page=2');

        $secondPage = $this->catalog(['name' => 'menta', 'sort' => 'name_desc', 'page' => 2]);
        $this->assertSame(['Menta 01'], $this->names($secondPage));
        $secondPage->assertSee('?name=menta&sort=name_desc&page=1');
    }

    // Spec 004, RF8: al ordenar o paginar, la página recargada queda en el título del listado.
    public function test_the_catalog_heading_has_the_catalogo_anchor(): void
    {
        Flavor::factory()->create();
        $this->assertMatchesRegularExpression('#<section id="catalogo" class="catalog-heading[^"]*"#', $this->catalog()->getContent());
    }

    public function test_the_catalogo_anchor_exists_without_results(): void
    {
        $response = $this->catalog(['name' => 'no-existe']);

        $this->assertSame(0, $response->viewData('flavors')->total());
        $this->assertSame(1, substr_count($response->getContent(), 'id="catalogo"'));
    }

    public function test_the_sort_form_goes_to_the_catalogo_anchor(): void
    {
        $form = $this->sortForm($this->catalog(['name' => 'menta'])->getContent());

        $this->assertMatchesRegularExpression('#^<form[^>]*action="'.preg_quote(route('home'), '#').'\#catalogo"#', $form);
    }

    public function test_the_filters_form_does_not_go_to_the_catalogo_anchor(): void
    {
        $form = $this->filtersForm($this->catalog(['sort' => 'name_desc'])->getContent());

        $this->assertMatchesRegularExpression('#^<form[^>]*action="'.preg_quote(route('home'), '#').'"#', $form);
        $this->assertStringNotContainsString('#catalogo', $form);
    }

    public function test_the_pagination_links_go_to_the_catalogo_anchor_keeping_the_sort_and_the_filters(): void
    {
        foreach (range(1, 25) as $number) {
            Flavor::factory()->create(['name' => sprintf('Menta %02d', $number), 'tobacco_type' => 'negro']);
        }

        $secondPage = $this->catalog(['name' => 'menta', 'tobacco_type' => 'negro', 'sort' => 'name_desc', 'page' => 2]);
        $content = $secondPage->getContent();

        foreach ([1, 3] as $page) {
            $this->assertStringContainsString('?name=menta&amp;tobacco_type=negro&amp;sort=name_desc&amp;page='.$page.'#catalogo"', $content);
        }

        // Todos los enlaces de la paginación llevan el ancla.
        preg_match_all('#href="([^"]*[?&](?:amp;)?page=\d+[^"]*)"#', $content, $links);
        $this->assertNotEmpty($links[1]);
        foreach ($links[1] as $link) {
            $this->assertStringEndsWith('#catalogo', $link);
        }
    }

    public function test_clear_removes_the_filters_but_keeps_the_sort(): void
    {
        $content = $this->catalog(['name' => 'menta', 'tobacco_type' => 'rojo', 'sort' => 'category'])->getContent();
        $this->assertSame(route('home', ['sort' => 'category']), $this->clearLink($content));

        $content = $this->catalog(['name' => 'menta', 'sort' => 'recent'])->getContent();
        $this->assertSame(route('home'), $this->clearLink($content));

        $content = $this->catalog(['name' => 'menta'])->getContent();
        $this->assertSame(route('home'), $this->clearLink($content));
    }

    /**
     * @return array<int, string>
     */
    private function groupTitles(string $content): array
    {
        preg_match_all('#<h3 class="catalog-group-title">\s*(.*?)\s*</h3>#s', $content, $matches);

        return array_map('html_entity_decode', $matches[1]);
    }

    // Spec 004, RF3: al ordenar por marca o categoría, la página se agrupa con títulos.
    public function test_sorting_by_brand_groups_the_flavors_under_their_brand(): void
    {
        $zeta = Brand::factory()->create(['name' => 'Zeta']);
        $alfa = Brand::factory()->create(['name' => 'Alfa & Co']);

        Flavor::factory()->create(['name' => 'Coco', 'brand_id' => $zeta->id]);
        Flavor::factory()->create(['name' => 'Menta', 'brand_id' => $alfa->id]);
        Flavor::factory()->create(['name' => 'Uva', 'brand_id' => $alfa->id]);

        $content = $this->catalog(['sort' => 'brand'])->getContent();

        $this->assertSame(['Alfa & Co', 'Zeta'], $this->groupTitles($content));
        $this->assertMatchesRegularExpression('#Alfa &amp; Co\s*</h3>.*Menta.*Uva.*Zeta\s*</h3>.*Coco#s', $content);
    }

    public function test_sorting_by_category_groups_the_flavors_and_names_the_ones_without_category(): void
    {
        $zeta = Category::factory()->create(['name' => 'Zeta']);
        $acido = Category::factory()->create(['name' => 'Ácido']);

        Flavor::factory()->create(['name' => 'Aaa suelto', 'category_id' => null]);
        Flavor::factory()->create(['name' => 'Coco', 'category_id' => $zeta->id]);
        Flavor::factory()->create(['name' => 'Menta', 'category_id' => $acido->id]);

        $content = $this->catalog(['sort' => 'category'])->getContent();

        $this->assertSame(['Ácido', 'Zeta', 'Sin categoría'], $this->groupTitles($content));

        // Cada sabor sigue teniendo su ventana de detalle.
        foreach (Flavor::all() as $flavor) {
            $this->detailModal($content, $flavor);
        }
    }

    public function test_a_group_that_continues_on_the_next_page_repeats_its_title(): void
    {
        $alfa = Brand::factory()->create(['name' => 'Alfa']);
        $beta = Brand::factory()->create(['name' => 'Beta']);

        foreach (range(1, 11) as $number) {
            Flavor::factory()->create(['name' => sprintf('Sabor %02d', $number), 'brand_id' => $alfa->id]);
        }
        foreach (range(1, 3) as $number) {
            Flavor::factory()->create(['name' => sprintf('Sabor %02d', $number), 'brand_id' => $beta->id]);
        }

        $this->assertSame(['Alfa', 'Beta'], $this->groupTitles($this->catalog(['sort' => 'brand'])->getContent()));
        $this->assertSame(['Beta'], $this->groupTitles($this->catalog(['sort' => 'brand', 'page' => 2])->getContent()));
    }

    public function test_other_sorts_have_no_group_titles(): void
    {
        Flavor::factory()->count(3)->create();

        foreach (['recent', 'name_asc', 'name_desc'] as $sort) {
            $content = $this->catalog(['sort' => $sort])->getContent();

            $this->assertSame([], $this->groupTitles($content), $sort);
            $this->assertStringNotContainsString('class="catalog-group-title"', $content);
        }
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

    // Spec 003, RF2: los desplegables de filtros se construyen desde los <select>, que siguen siendo el campo enviado.
    public function test_the_brand_and_category_filters_use_the_filter_dropdown(): void
    {
        $content = $this->catalog()->getContent();

        $this->assertMatchesRegularExpression('/<select name="brand_id" id="brand_id"[^>]*data-filter-dropdown/', $content);
        $this->assertMatchesRegularExpression('/<select name="category_id" id="category_id"[^>]*data-filter-dropdown/', $content);
        $this->assertMatchesRegularExpression('/<input\s+type="text"\s+name="tobacco_type"/', $content);
        $this->assertSame(1, substr_count($content, 'function initFilterDropdowns'));
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
        // El selector de orden siempre tiene una opción elegida; las de marca y categoría (ids) no.
        $this->assertDoesNotMatchRegularExpression('/<option value="\d+" selected>/', $response->getContent());
    }

    // RF-61 (desde la spec 003, el enlace está en la ventana de cada sabor: hace falta uno)
    public function test_the_catalog_create_mix_link_goes_to_the_creation_form(): void
    {
        Flavor::factory()->create();

        $this->assertCreateMixLink($this->catalog());
    }

    public function test_the_favorites_create_mix_link_goes_to_the_creation_form(): void
    {
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => auth()->id(), 'flavor_id' => $flavor->id]);

        $this->assertCreateMixLink($this->get(route('favorites.index'))->assertOk());
    }

    // Spec 003, RF3 y RF4: cada sabor del catálogo abre su ventana con el diseño de la tarjeta del catálogo.
    public function test_each_flavor_of_the_catalog_has_its_detail_modal(): void
    {
        $flavor = Flavor::factory()->create([
            'name' => 'Menta Glacial',
            'description' => str_repeat('Frescor intenso de menta. ', 8).'Final de la descripción.',
            'ingredients_text' => 'Menta, hielo, lima y un toque de eucalipto para el final',
            'tobacco_type' => 'sin_nicotina',
        ]);
        $other = Flavor::factory()->create(['name' => 'Otro sabor']);

        $content = $this->catalog()->getContent();

        $modal = $this->detailModal($content, $flavor);
        $this->assertStringContainsString('Menta Glacial', $modal);
        $this->assertStringContainsString('Final de la descripción.', $modal);
        $this->assertStringContainsString('un toque de eucalipto para el final', $modal);
        $this->assertStringContainsString('Sin nicotina', $modal);
        $this->assertStringContainsString($flavor->brand->name, $modal);
        $this->assertStringContainsString($flavor->category->name, $modal);
        $this->assertStringContainsString('data-bs-dismiss="modal"', $modal);
        $this->detailModal($content, $other);

        // La tarjeta abre su ventana con clic o Intro; el corazón no la abre.
        $this->assertMatchesRegularExpression('/tabindex="0"[^>]*onclick="openFlavorModal\('.$flavor->id.'\)"[^>]*onkeydown="if \(event\.key === \'Enter\'/', $content);
        $this->assertMatchesRegularExpression('/action="'.preg_quote(route('favorites.store', $flavor), '/').'"[^>]*onclick="event\.stopPropagation\(\);"/', $content);

        // Una sola copia del código y ninguna de la ventana antigua.
        $this->assertSame(1, substr_count($content, 'function openFlavorModal'));
        $this->assertStringNotContainsString('flavorModalOverlay', $content);
    }

    public function test_a_flavor_without_description_or_ingredients_shows_default_texts_in_its_modal(): void
    {
        $flavor = Flavor::factory()->create(['category_id' => null]);

        $modal = $this->detailModal($this->catalog()->getContent(), $flavor);

        $this->assertStringContainsString('Sin descripción disponible.', $modal);
        $this->assertStringContainsString('Sin ingredientes definidos', $modal);
        $this->assertStringContainsString('Rubio', $modal);
    }

    private function detailModal(string $content, Flavor $flavor): string
    {
        $start = strpos($content, 'id="flavorModal-'.$flavor->id.'"');
        $this->assertNotFalse($start, "Falta la ventana del sabor {$flavor->id}");
        $this->assertSame(1, substr_count($content, 'id="flavorModal-'.$flavor->id.'"'));

        $end = strpos($content, 'class="modal fade', $start);

        return $end === false ? substr($content, $start) : substr($content, $start, $end - $start);
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

    // Spec 006: el setUp inicia sesión; aquí se vuelve a visitante.
    private function asGuest(): void
    {
        $this->app['auth']->forgetGuards();
        $this->assertGuest();
    }

    // Spec 006, RF1 y RF2: un visitante ve el catálogo con filtros, orden y paginación, solo con sabores públicos.
    public function test_a_guest_sees_the_catalog_with_filters_sort_and_pagination(): void
    {
        $brand = Brand::factory()->create();
        Flavor::factory()->for($brand)->count(13)->sequence(fn ($s) => ['name' => sprintf('Menta %02d', $s->index)])->create();
        Flavor::factory()->for($brand)->create(['name' => 'Fresa']);
        Flavor::factory()->for($brand)->notPublic()->create(['name' => 'Menta oculta']);

        $this->asGuest();

        $all = $this->catalog();
        $this->assertCount(12, $this->names($all));
        $all->assertDontSee('Menta oculta');

        $filtered = $this->catalog(['name' => 'menta', 'brand_id' => $brand->id, 'sort' => 'name_desc']);
        $this->assertSame('Menta 12', $this->names($filtered)[0]);
        $this->assertSame(13, $filtered->viewData('flavors')->total());

        $secondPage = $this->catalog(['name' => 'menta', 'sort' => 'name_desc', 'page' => 2]);
        $this->assertSame(['Menta 00'], $this->names($secondPage));

        $this->assertGuest();
    }

    // Spec 006, RF2: sin sesión no hay favoritos marcados y no se consulta la tabla favorites.
    public function test_a_guest_has_no_favorites_and_the_table_is_not_queried(): void
    {
        $flavor = Flavor::factory()->create(['name' => 'Menta']);
        Favorite::create(['user_id' => User::factory()->create()->id, 'flavor_id' => $flavor->id]);

        $this->asGuest();

        DB::enableQueryLog();
        $response = $this->catalog();
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertSame([], $response->viewData('favoriteIds'));
        $response->assertDontSee('favorite-btn active', false);
        $this->assertFalse($queries->contains(fn ($sql) => str_contains($sql, 'favorites')));
    }

    // Spec 006, RF1: Favoritos, Mezclas y Admin siguen pidiendo sesión.
    public function test_a_guest_is_still_sent_to_login_from_favorites_mixes_and_admin(): void
    {
        $this->asGuest();

        foreach (['favorites.index', 'mixes.index', 'mixes.create', 'admin.index', 'admin.flavors.index'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
    }

    // Spec 006, RF3: sin sesión, el corazón y "Crear mezcla" son enlaces a login, no formularios.
    public function test_for_a_guest_the_heart_and_create_mix_link_to_login(): void
    {
        $flavor = Flavor::factory()->create(['name' => 'Menta']);

        $this->asGuest();

        $response = $this->catalog();

        $response->assertSee('<a href="'.route('login').'" class="favorite-btn"', false);
        $response->assertSee('aria-label="Inicia sesión para añadir Menta a favoritos"', false);
        $response->assertDontSee('action="'.route('favorites.store', $flavor).'"', false);
        $response->assertDontSee('data-favorite-toggle', false);

        $response->assertSee('<a href="'.route('login').'" class="btn flavor-detail-primary">Crear mezcla</a>', false);
        $response->assertDontSee('href="'.route('mixes.create').'"', false);
    }

    // Spec 006, RF3: con sesión, el corazón y "Crear mezcla" siguen igual.
    public function test_with_a_session_the_heart_and_create_mix_do_not_change(): void
    {
        $flavor = Flavor::factory()->create(['name' => 'Menta']);

        $response = $this->catalog();

        $response->assertSee('action="'.route('favorites.store', $flavor).'"', false);
        $response->assertSee('data-favorite-toggle', false);
        $response->assertSee('aria-label="Añadir Menta a favoritos"', false);
        $response->assertSee('<a href="'.route('mixes.create').'" class="btn flavor-detail-primary">Crear mezcla</a>', false);
        $response->assertDontSee('Inicia sesión para añadir', false);
    }

    // Spec 006, RF5: el visitante ve "Iniciar sesión" y "Registrarse" y solo enlaces a Sabores y Marcas, en la portada y en Marcas.
    public function test_a_guest_sees_the_access_buttons_and_only_flavors_and_brands(): void
    {
        $brand = Brand::factory()->create();

        $this->asGuest();

        foreach ([route('home'), route('brands.index'), route('brands.show', $brand)] as $url) {
            $response = $this->get($url)->assertOk();

            $response->assertSee('href="'.route('login').'" class="btn us-access us-access-login">Iniciar sesión</a>', false);
            $response->assertSee('href="'.route('register').'" class="btn us-access us-access-register">Registrarse</a>', false);
            $response->assertSee('href="'.route('home').'" class="us-tab', false);
            $response->assertSee('href="'.route('brands.index').'" class="us-tab', false);
            // Spec 007: el visitante también ve Mezclas, que lleva a las públicas.
            $response->assertSee('href="'.route('mixes.public').'" class="us-tab', false);
            $response->assertSee('href="'.route('mixes.public').'" class="us-link', false);
            $response->assertSee('href="'.route('brands.index').'" class="us-link', false);

            foreach (['favorites.index', 'mixes.index', 'admin.index'] as $hidden) {
                $response->assertDontSee('href="'.route($hidden).'"', false);
            }

            $response->assertDontSee('Cerrar sesión');
        }
    }

    // Spec 006, RF5: con sesión, la navegación no cambia.
    public function test_with_a_session_the_navigation_does_not_change(): void
    {
        $response = $this->catalog();

        foreach (['home', 'favorites.index', 'mixes.index', 'brands.index'] as $name) {
            $response->assertSee('href="'.route($name).'" class="us-tab', false);
        }

        $response->assertSee('Cerrar sesión');
        $response->assertDontSee('class="btn us-access', false);
    }

    private function assertNoEnglishPagination(TestResponse $response): void
    {
        foreach (['Showing', 'Previous', 'Next', 'pagination.previous', 'pagination.next'] as $text) {
            $response->assertDontSee($text, false);
        }
    }
}
