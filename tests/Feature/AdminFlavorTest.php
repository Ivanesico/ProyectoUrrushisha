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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminFlavorTest extends TestCase
{
    use RefreshDatabase;

    // PNG real de 1x1: GD está desactivado y no se puede usar UploadedFile::fake()->image()
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private const TOBACCO_TYPE_MESSAGE = 'El tipo de tabaco debe ser uno de estos valores: rubio, negro, herbal, base, rojo, sin_nicotina.';

    private const ERROR_A_REASON = 'Error conocido A (excepción expresa de la spec 001): un array en un campo de texto provoca un error de servidor al volver al formulario.';

    private const ERROR_B_REASON = 'Error conocido B (excepción expresa de la spec 001): el formulario no muestra el error de is_public.';

    // Casos de invalidFields omitidos en los formularios; los demás se siguen ejecutando
    private const KNOWN_FORM_ERRORS = [
        'nombre como array' => self::ERROR_A_REASON,
        'descripción como array' => self::ERROR_A_REASON,
        'ingredientes como array' => self::ERROR_A_REASON,
        'público no booleano' => self::ERROR_B_REASON,
    ];

    private User $admin;

    private Brand $brand;

    private Category $category;

    /** @var array<int, string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // RF-60: los mensajes no dependen del idioma de la aplicación
        config(['app.locale' => 'en']);
        app()->setLocale('en');

        Storage::fake('public');

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->brand = Brand::factory()->create(['name' => 'Marca base']);
        $this->category = Category::factory()->create(['name' => 'Categoría base']);

        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Menta fresca',
            'description' => 'Muy refrescante',
            'tobacco_type' => 'rubio',
            'ingredients_text' => 'Menta, hielo',
            'is_public' => '1',
        ], $overrides);
    }

    private function realFile(string $contents, string $clientName, string $clientMime, ?int $error = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'flavor');
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $clientName, $clientMime, $error, true);
    }

    private function realPng(string $clientName = 'foto.png'): UploadedFile
    {
        return $this->realFile(base64_decode(self::PNG_BASE64), $clientName, 'image/png');
    }

    private function storedFiles(): array
    {
        return Storage::disk('public')->allFiles();
    }

    private function skipKnownFormError(): void
    {
        $reason = self::KNOWN_FORM_ERRORS[$this->dataName()] ?? null;

        if ($reason !== null) {
            $this->markTestSkipped($reason);
        }
    }

    // RF-43

    public function test_admin_creates_a_flavor_and_sees_a_message(): void
    {
        $response = $this->followingRedirects()->post(route('admin.flavors.store'), $this->payload());

        $response->assertOk()->assertSee('Sabor creado correctamente.')->assertSee('Menta fresca');

        $this->assertDatabaseHas('flavors', [
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Menta fresca',
            'description' => 'Muy refrescante',
            'tobacco_type' => 'rubio',
            'ingredients_text' => 'Menta, hielo',
            'image_url' => null,
            'created_by' => $this->admin->id,
            'is_public' => true,
        ]);
    }

    public function test_is_public_unchecked_is_saved_as_not_public(): void
    {
        $payload = $this->payload();
        unset($payload['is_public']);

        $this->post(route('admin.flavors.store'), $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('flavors', ['name' => 'Menta fresca', 'is_public' => false]);
    }

    public function test_admin_updates_a_flavor_and_sees_a_message(): void
    {
        $flavor = Flavor::factory()->create(['brand_id' => $this->brand->id, 'name' => 'Antiguo']);
        $otherBrand = Brand::factory()->create();

        $response = $this->followingRedirects()->put(route('admin.flavors.update', $flavor), $this->payload([
            'brand_id' => $otherBrand->id,
            'name' => 'Renovado',
            'tobacco_type' => 'negro',
        ]));

        $response->assertOk()->assertSee('Sabor actualizado correctamente.')->assertDontSee('Sabor creado');

        $flavor->refresh();
        $this->assertSame('Renovado', $flavor->name);
        $this->assertSame($otherBrand->id, $flavor->brand_id);
        $this->assertSame('negro', $flavor->tobacco_type);
    }

    public function test_admin_deletes_an_unused_flavor_and_its_favorites_but_not_its_image_file(): void
    {
        Storage::disk('public')->put('flavors/guardada.png', 'contenido');
        $flavor = Flavor::factory()->create(['image_url' => 'flavors/guardada.png']);
        Favorite::create(['user_id' => $this->admin->id, 'flavor_id' => $flavor->id]);

        $response = $this->followingRedirects()->delete(route('admin.flavors.destroy', $flavor));

        $response->assertOk()->assertSee('Sabor eliminado con éxito.');
        $this->assertDatabaseMissing('flavors', ['id' => $flavor->id]);
        $this->assertDatabaseMissing('favorites', ['flavor_id' => $flavor->id]);
        Storage::disk('public')->assertExists('flavors/guardada.png');
    }

    // RF-44 y spec 005, RF2: los órdenes de la portada más los dos de ID; cualquier otro valor usa "recent".

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<int, string>, 2: string}>
     */
    public static function sortCases(): array
    {
        return [
            'id asc' => [['sort' => 'id_asc'], ['Menta', 'Anís', 'Coco'], 'id_asc'],
            'id desc' => [['sort' => 'id_desc'], ['Coco', 'Anís', 'Menta'], 'id_desc'],
            'nombre asc' => [['sort' => 'name_asc'], ['Anís', 'Coco', 'Menta'], 'name_asc'],
            'nombre desc' => [['sort' => 'name_desc'], ['Menta', 'Coco', 'Anís'], 'name_desc'],
            'marca' => [['sort' => 'brand'], ['Anís', 'Menta', 'Coco'], 'brand'],
            'categoría' => [['sort' => 'category'], ['Coco', 'Menta', 'Anís'], 'category'],
            'recientes' => [['sort' => 'recent'], ['Anís', 'Coco', 'Menta'], 'recent'],
            'por defecto' => [[], ['Anís', 'Coco', 'Menta'], 'recent'],
            'criterio no permitido' => [['sort' => 'created_by', 'direction' => 'asc'], ['Anís', 'Coco', 'Menta'], 'recent'],
            'clave antigua con dirección' => [['sort' => 'name', 'direction' => 'asc'], ['Anís', 'Coco', 'Menta'], 'recent'],
            'criterio como array' => [['sort' => ['name_asc'], 'direction' => ['asc']], ['Anís', 'Coco', 'Menta'], 'recent'],
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<int, string>  $expected
     */
    #[DataProvider('sortCases')]
    public function test_flavor_list_can_be_sorted(array $query, array $expected, string $expectedSort): void
    {
        $brandA = Brand::factory()->create(['name' => 'Alfa']);
        $brandB = Brand::factory()->create(['name' => 'Beta']);
        $brandC = Brand::factory()->create(['name' => 'Gamma']);
        $categoryA = Category::factory()->create(['name' => 'Alfa']);
        $categoryB = Category::factory()->create(['name' => 'Beta']);
        $categoryC = Category::factory()->create(['name' => 'Gamma']);

        // Por id: Menta, Anís, Coco. Por marca: Anís, Menta, Coco. Por categoría: Coco, Menta, Anís.
        // Por fecha de alta (la más reciente primero): Anís, Coco, Menta.
        Flavor::factory()->create(['name' => 'Menta', 'brand_id' => $brandB->id, 'category_id' => $categoryB->id, 'created_at' => now()->subDays(3)]);
        Flavor::factory()->create(['name' => 'Anís', 'brand_id' => $brandA->id, 'category_id' => $categoryC->id, 'created_at' => now()->subDay()]);
        Flavor::factory()->create(['name' => 'Coco', 'brand_id' => $brandC->id, 'category_id' => $categoryA->id, 'created_at' => now()->subDays(2)]);

        $response = $this->get(route('admin.flavors.index', $query))
            ->assertOk()
            ->assertSeeInOrder($expected)
            ->assertViewHas('flavors', fn ($flavors) => $flavors->pluck('name')->all() === $expected);

        $this->assertSame($expectedSort, $response->viewData('sort'));
    }

    // Spec 005, RF1, RF3 y RF4: filtros, valores raros y paginación del listado del admin.

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, string>
     */
    private function adminNames(array $query = []): array
    {
        $response = $this->get(route('admin.flavors.index', $query))->assertOk();

        return $response->viewData('flavors')->pluck('name')->sort()->values()->all();
    }

    public function test_without_filters_the_list_shows_public_and_hidden_flavors(): void
    {
        Flavor::factory()->create(['name' => 'Visible']);
        Flavor::factory()->notPublic()->create(['name' => 'Oculto']);

        $this->assertSame(['Oculto', 'Visible'], $this->adminNames());
    }

    public function test_the_name_filter_ignores_case_and_accents(): void
    {
        Flavor::factory()->create(['name' => 'Ménta Fresca']);
        Flavor::factory()->notPublic()->create(['name' => 'MENTA oculta']);
        Flavor::factory()->create(['name' => 'Coco']);

        $this->assertSame(['MENTA oculta', 'Ménta Fresca'], $this->adminNames(['name' => 'menta']));
    }

    public function test_it_filters_by_brand_and_by_category(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        Flavor::factory()->create(['name' => 'De la marca', 'brand_id' => $brand->id]);
        Flavor::factory()->notPublic()->create(['name' => 'De la categoría', 'category_id' => $category->id]);
        Flavor::factory()->create(['name' => 'Otro']);

        $this->assertSame(['De la marca'], $this->adminNames(['brand_id' => $brand->id]));
        $this->assertSame(['De la categoría'], $this->adminNames(['category_id' => $category->id]));
    }

    public function test_the_tobacco_type_filter_is_a_partial_text_search(): void
    {
        Flavor::factory()->create(['name' => 'Negro', 'tobacco_type' => 'negro']);
        Flavor::factory()->create(['name' => 'Sin nicotina', 'tobacco_type' => 'sin_nicotina']);
        Flavor::factory()->create(['name' => 'Rubio', 'tobacco_type' => 'rubio']);

        $this->assertSame(['Negro'], $this->adminNames(['tobacco_type' => 'NEG']));
        $this->assertSame(['Sin nicotina'], $this->adminNames(['tobacco_type' => 'nicotina']));
    }

    public function test_it_filters_by_visibility(): void
    {
        Flavor::factory()->create(['name' => 'Visible']);
        Flavor::factory()->notPublic()->create(['name' => 'Oculto']);

        $this->assertSame(['Visible'], $this->adminNames(['visibility' => 'public']));
        $this->assertSame(['Oculto'], $this->adminNames(['visibility' => 'hidden']));
        $this->assertSame(['Oculto', 'Visible'], $this->adminNames(['visibility' => '']));
    }

    public function test_it_filters_the_flavors_without_image_or_without_description(): void
    {
        Flavor::factory()->create(['name' => 'Completo', 'image_url' => 'flavors/a.png', 'description' => 'Rico']);
        Flavor::factory()->create(['name' => 'Imagen nula', 'image_url' => null, 'description' => 'Rico']);
        Flavor::factory()->notPublic()->create(['name' => 'Imagen vacía', 'image_url' => '', 'description' => 'Rico']);
        Flavor::factory()->create(['name' => 'Descripción nula', 'image_url' => 'flavors/b.png', 'description' => null]);
        Flavor::factory()->notPublic()->create(['name' => 'Descripción vacía', 'image_url' => 'flavors/c.png', 'description' => '']);

        $this->assertSame(['Imagen nula', 'Imagen vacía'], $this->adminNames(['missing' => 'image']));
        $this->assertSame(['Descripción nula', 'Descripción vacía'], $this->adminNames(['missing' => 'description']));
        $this->assertCount(5, $this->adminNames(['missing' => '']));
    }

    public function test_the_filters_are_combined(): void
    {
        $brand = Brand::factory()->create();
        Flavor::factory()->notPublic()->create(['name' => 'Menta oculta', 'brand_id' => $brand->id, 'tobacco_type' => 'negro']);
        Flavor::factory()->create(['name' => 'Menta visible', 'brand_id' => $brand->id, 'tobacco_type' => 'negro']);
        Flavor::factory()->notPublic()->create(['name' => 'Menta rubia', 'brand_id' => $brand->id, 'tobacco_type' => 'rubio']);
        Flavor::factory()->notPublic()->create(['name' => 'Menta de otra marca', 'tobacco_type' => 'negro']);
        Flavor::factory()->notPublic()->create(['name' => 'Coco oculto', 'brand_id' => $brand->id, 'tobacco_type' => 'negro']);
        Flavor::factory()->notPublic()->create(['name' => 'Menta con foto', 'brand_id' => $brand->id, 'tobacco_type' => 'negro', 'image_url' => 'flavors/x.png']);

        $this->assertSame(['Menta oculta'], $this->adminNames([
            'name' => 'menta',
            'brand_id' => $brand->id,
            'tobacco_type' => 'negro',
            'visibility' => 'hidden',
            'missing' => 'image',
        ]));
    }

    public function test_the_ingredient_filter_is_ignored(): void
    {
        Flavor::factory()->create(['name' => 'Con menta', 'ingredients_text' => 'menta']);
        Flavor::factory()->create(['name' => 'Sin ingredientes']);

        $this->assertSame(['Con menta', 'Sin ingredientes'], $this->adminNames(['ingredient' => 'menta']));
        $this->assertCount(2, $this->get('/admin/flavors?ingredient[]=menta')->assertOk()->viewData('flavors'));
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function oddValues(): array
    {
        $all = ['Oculto', 'Visible'];

        return [
            'marca no entera' => ['/admin/flavors?brand_id=abc', []],
            'marca negativa' => ['/admin/flavors?brand_id=-1', []],
            'marca como array' => ['/admin/flavors?brand_id[]=1', []],
            'categoría decimal' => ['/admin/flavors?category_id=1.5', []],
            'categoría como array' => ['/admin/flavors?category_id[a]=1', []],
            'nombre como array' => ['/admin/flavors?name[]=menta', []],
            'tipo como array' => ['/admin/flavors?tobacco_type[]=negro', []],
            'visibilidad desconocida' => ['/admin/flavors?visibility=todos', $all],
            'visibilidad como array' => ['/admin/flavors?visibility[]=hidden', $all],
            'incompletos desconocido' => ['/admin/flavors?missing=ingredients_text', $all],
            'incompletos como array' => ['/admin/flavors?missing[]=image', $all],
            'orden como array asociativo' => ['/admin/flavors?sort[id_asc]=1', $all],
            'página como texto' => ['/admin/flavors?page=abc', $all],
            'página como array' => ['/admin/flavors?page[]=2', $all],
        ];
    }

    /**
     * @param  array<int, string>  $expected
     */
    #[DataProvider('oddValues')]
    public function test_odd_values_never_give_a_server_error(string $url, array $expected): void
    {
        Flavor::factory()->create(['name' => 'Visible']);
        Flavor::factory()->notPublic()->create(['name' => 'Oculto']);

        $response = $this->get($url)->assertOk();

        $this->assertSame($expected, $response->viewData('flavors')->pluck('name')->sort()->values()->all());
    }

    public function test_it_shows_fifty_flavors_per_page_keeping_the_filters_and_the_sort(): void
    {
        foreach (range(1, 51) as $number) {
            Flavor::factory()->notPublic()->create(['name' => sprintf('Menta %02d', $number)]);
        }
        Flavor::factory()->create(['name' => 'Coco']);

        $query = ['name' => 'menta', 'visibility' => 'hidden', 'sort' => 'name_desc'];

        $flavors = $this->get(route('admin.flavors.index', $query))->assertOk()->viewData('flavors');
        $this->assertSame(51, $flavors->total());
        $this->assertCount(50, $flavors);
        $this->assertSame('Menta 51', $flavors->first()->name);

        $nextUrl = $flavors->nextPageUrl();
        foreach (['name=menta', 'visibility=hidden', 'sort=name_desc', 'page=2'] as $part) {
            $this->assertStringContainsString($part, $nextUrl);
        }

        $second = $this->get(route('admin.flavors.index', $query + ['page' => 2]))->assertOk();
        $this->assertSame(['Menta 01'], $second->viewData('flavors')->pluck('name')->all());
    }

    public function test_a_page_out_of_range_redirects_to_the_last_page_keeping_the_filters(): void
    {
        foreach (range(1, 51) as $number) {
            Flavor::factory()->create(['name' => sprintf('Menta %02d', $number)]);
        }

        $this->get(route('admin.flavors.index', ['name' => 'menta', 'sort' => 'id_desc', 'page' => 7]))
            ->assertRedirect(route('admin.flavors.index', ['name' => 'menta', 'sort' => 'id_desc', 'page' => 2]));
    }

    public function test_a_page_out_of_range_without_results_does_not_redirect(): void
    {
        $this->get(route('admin.flavors.index', ['name' => 'nada', 'page' => 3]))->assertOk();
    }

    // Spec 005, RF1, RF2, RF4 y RF6: la vista del listado (panel, orden, contador, paginación y estado vacío).

    public function test_the_filter_panel_shows_every_field_with_its_current_value(): void
    {
        $brand = Brand::factory()->create(['name' => 'Marca elegida']);
        $category = Category::factory()->create(['name' => 'Categoría elegida']);

        $html = $this->get(route('admin.flavors.index', [
            'name' => 'menta',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'tobacco_type' => 'negro',
            'visibility' => 'hidden',
            'missing' => 'description',
            'sort' => 'name_desc',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('id="filtersContent"', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="name"[^>]*value="menta"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="tobacco_type"[^>]*value="negro"/', $html);
        $this->assertMatchesRegularExpression('/<option value="'.$brand->id.'" selected>\s*Marca elegida/', $html);
        $this->assertMatchesRegularExpression('/<option value="'.$category->id.'" selected>\s*Categoría elegida/', $html);
        $this->assertStringContainsString('<option value="hidden" selected>No públicos</option>', $html);
        $this->assertStringContainsString('<option value="description" selected>Sin descripción</option>', $html);
        $this->assertStringContainsString('<option value="public">Públicos</option>', $html);
        $this->assertStringContainsString('<option value="image">Sin imagen</option>', $html);
        $this->assertStringContainsString('Aplicar filtros', $html);
        $this->assertStringNotContainsString('name="ingredient"', $html);
    }

    public function test_the_filter_form_keeps_the_sort_and_clear_keeps_only_the_sort(): void
    {
        $html = $this->get(route('admin.flavors.index', ['name' => 'menta', 'sort' => 'id_desc']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<form[^>]*class="[^"]*filters-form[^"]*"[^>]*>\s*<input type="hidden" name="sort" value="id_desc">/', $html);
        $this->assertStringContainsString('href="'.e(route('admin.flavors.index', ['sort' => 'id_desc'])).'"', $html);
    }

    public function test_clear_without_a_chosen_sort_goes_to_the_plain_list(): void
    {
        $this->get(route('admin.flavors.index', ['name' => 'menta']))
            ->assertOk()
            ->assertSee('href="'.e(route('admin.flavors.index')).'"', false);
    }

    public function test_the_sort_selector_lists_every_option_and_carries_the_active_filters(): void
    {
        $html = $this->get(route('admin.flavors.index', [
            'name' => 'menta',
            'visibility' => 'hidden',
            'sort' => 'brand',
            'page' => 2,
        ]))->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<form[^>]*class="sort-form"[^>]*>(.*?)<\/form>/s', $html, $match));
        $sortForm = $match[1];

        $this->assertStringContainsString('Ordenar por:', $sortForm);
        $this->assertStringContainsString('<input type="hidden" name="name" value="menta">', $sortForm);
        $this->assertStringContainsString('<input type="hidden" name="visibility" value="hidden">', $sortForm);
        // Al ordenar se vuelve a la página 1 y no se envían filtros vacíos.
        $this->assertStringNotContainsString('name="page"', $sortForm);
        $this->assertStringNotContainsString('name="missing"', $sortForm);
        $this->assertStringContainsString('onchange="this.form.submit()"', $sortForm);
        $this->assertStringContainsString('<option value="brand" selected>Marca</option>', $sortForm);

        foreach (Flavor::ADMIN_SORTS as $value => $label) {
            $this->assertStringContainsString('<option value="'.$value.'"', $sortForm);
            $this->assertStringContainsString('>'.e($label).'</option>', $sortForm);
        }
    }

    public function test_the_default_sort_is_selected_when_none_is_chosen(): void
    {
        $this->get(route('admin.flavors.index'))
            ->assertOk()
            ->assertSee('<option value="recent" selected>Añadidos recientemente</option>', false);
    }

    public function test_the_headers_no_longer_sort_the_list(): void
    {
        Flavor::factory()->create();

        $html = $this->get(route('admin.flavors.index'))->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<thead.*?<\/thead>/s', $html, $match));
        $this->assertStringNotContainsString('<a ', $match[0]);
        $this->assertStringNotContainsString('direction=', $html);
    }

    public function test_it_shows_how_many_flavors_match(): void
    {
        Flavor::factory()->count(3)->create();
        Flavor::factory()->create(['name' => 'Menta']);

        $this->get(route('admin.flavors.index'))->assertOk()->assertSee('4 sabores');
        $this->get(route('admin.flavors.index', ['name' => 'menta']))->assertOk()->assertSee('1 sabor')->assertDontSee('1 sabores');
    }

    public function test_the_pagination_links_keep_the_filters_and_the_sort(): void
    {
        foreach (range(1, 51) as $number) {
            Flavor::factory()->notPublic()->create(['name' => sprintf('Menta %02d', $number)]);
        }

        $query = ['name' => 'menta', 'visibility' => 'hidden', 'sort' => 'name_asc'];

        $this->get(route('admin.flavors.index', $query))
            ->assertOk()
            ->assertSee('51 sabores')
            ->assertSee('href="'.e(route('admin.flavors.index', $query + ['page' => 2])).'"', false);
    }

    public function test_without_matches_it_offers_to_clear_the_filters(): void
    {
        Flavor::factory()->create(['name' => 'Coco']);

        $this->get(route('admin.flavors.index', ['name' => 'menta', 'sort' => 'name_asc']))
            ->assertOk()
            ->assertSee('No hay sabores que coincidan')
            ->assertSee('0 sabores')
            ->assertDontSee('No hay sabores registrados.')
            ->assertSee('href="'.e(route('admin.flavors.index', ['sort' => 'name_asc'])).'"', false);
    }

    public function test_an_empty_list_without_filters_says_there_are_no_flavors(): void
    {
        $this->get(route('admin.flavors.index'))
            ->assertOk()
            ->assertSee('No hay sabores registrados.')
            ->assertDontSee('No hay sabores que coincidan');
    }

    // RF-45

    /**
     * @return array<string, array{0: string}>
     */
    public static function allowedTobaccoTypes(): array
    {
        return [
            'rubio' => ['rubio'],
            'negro' => ['negro'],
            'herbal' => ['herbal'],
            'base' => ['base'],
            'rojo' => ['rojo'],
            'sin_nicotina' => ['sin_nicotina'],
        ];
    }

    #[DataProvider('allowedTobaccoTypes')]
    public function test_every_tobacco_type_in_the_list_is_accepted(string $type): void
    {
        $flavor = Flavor::factory()->create(['tobacco_type' => null]);

        $this->post(route('admin.flavors.store'), $this->payload(['tobacco_type' => $type]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('flavors', ['name' => 'Menta fresca', 'tobacco_type' => $type]);

        $this->put(route('admin.flavors.update', $flavor), $this->payload(['name' => 'Otro', 'tobacco_type' => $type]))
            ->assertSessionHasNoErrors();
        $this->assertSame($type, $flavor->fresh()->tobacco_type);
    }

    public function test_the_forms_offer_the_tobacco_types_as_a_dropdown(): void
    {
        $flavor = Flavor::factory()->create(['tobacco_type' => null]);

        foreach ([route('admin.flavors.create'), route('admin.flavors.edit', $flavor)] as $url) {
            $response = $this->get($url)
                ->assertOk()
                ->assertSee('<select name="tobacco_type"', false)
                ->assertDontSee('<input type="text" name="tobacco_type"', false)
                ->assertSee('<option value="">Sin especificar</option>', false);

            foreach (Flavor::TOBACCO_TYPES as $type) {
                $response->assertSee('<option value="'.$type.'"', false)
                    ->assertDontSee('<option value="'.$type.'" selected>', false);
            }
        }
    }

    public function test_the_edit_form_selects_the_saved_tobacco_type(): void
    {
        $flavor = Flavor::factory()->create(['tobacco_type' => 'base']);

        $this->get(route('admin.flavors.edit', $flavor))
            ->assertSee('<option value="base" selected>', false)
            ->assertDontSee('<option value="rubio" selected>', false);
    }

    public function test_a_rejected_form_keeps_the_chosen_tobacco_type(): void
    {
        $flavor = Flavor::factory()->create(['tobacco_type' => 'rubio']);

        $this->from(route('admin.flavors.edit', $flavor))
            ->followingRedirects()
            ->put(route('admin.flavors.update', $flavor), $this->payload(['name' => '', 'tobacco_type' => 'rojo']))
            ->assertSee('<option value="rojo" selected>', false)
            ->assertDontSee('<option value="rubio" selected>', false);

        $this->from(route('admin.flavors.create'))
            ->followingRedirects()
            ->post(route('admin.flavors.store'), $this->payload(['name' => '', 'tobacco_type' => 'base']))
            ->assertSee('<option value="base" selected>', false);
    }

    public function test_an_empty_tobacco_type_is_accepted(): void
    {
        $this->post(route('admin.flavors.store'), $this->payload(['tobacco_type' => '']))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('flavors', ['name' => 'Menta fresca', 'tobacco_type' => null]);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidTobaccoTypes(): array
    {
        return [
            'fuera de la lista' => ['Virginia'],
            'con mayúsculas' => ['Rubio'],
            'como array' => [['rubio']],
        ];
    }

    #[DataProvider('invalidTobaccoTypes')]
    public function test_a_tobacco_type_outside_the_list_is_rejected_with_the_allowed_values(mixed $type): void
    {
        $flavor = Flavor::factory()->create(['tobacco_type' => 'negro']);

        $this->post(route('admin.flavors.store'), $this->payload(['tobacco_type' => $type]))
            ->assertSessionHasErrors(['tobacco_type' => self::TOBACCO_TYPE_MESSAGE]);
        $this->assertDatabaseMissing('flavors', ['name' => 'Menta fresca']);

        $this->put(route('admin.flavors.update', $flavor), $this->payload(['tobacco_type' => $type]))
            ->assertSessionHasErrors(['tobacco_type' => self::TOBACCO_TYPE_MESSAGE]);
        $this->assertSame('negro', $flavor->fresh()->tobacco_type);
    }

    public function test_the_tobacco_type_error_is_shown_on_the_form(): void
    {
        $response = $this->followingRedirects()
            ->from(route('admin.flavors.create'))
            ->post(route('admin.flavors.store'), $this->payload(['tobacco_type' => 'Virginia']));

        $response->assertOk()->assertSee(self::TOBACCO_TYPE_MESSAGE)->assertDontSee('value="Virginia"', false);

        foreach (Flavor::TOBACCO_TYPES as $type) {
            $response->assertDontSee('<option value="'.$type.'" selected>', false);
        }
    }

    public function test_an_array_as_tobacco_type_does_not_break_the_forms(): void
    {
        $this->markTestSkipped(self::ERROR_A_REASON);

        $flavor = Flavor::factory()->create();

        $this->followingRedirects()
            ->from(route('admin.flavors.create'))
            ->post(route('admin.flavors.store'), $this->payload(['tobacco_type' => ['rubio']]))
            ->assertOk()
            ->assertSee(self::TOBACCO_TYPE_MESSAGE);

        $this->followingRedirects()
            ->from(route('admin.flavors.edit', $flavor))
            ->put(route('admin.flavors.update', $flavor), $this->payload(['tobacco_type' => ['rubio']]))
            ->assertOk()
            ->assertSee(self::TOBACCO_TYPE_MESSAGE);
    }

    // RF-46 y RF-54

    /**
     * @return array<string, array{0: string, 1: mixed, 2: string}>
     */
    public static function invalidFields(): array
    {
        return [
            'marca obligatoria' => ['brand_id', '', 'La marca es obligatoria.'],
            'marca con texto' => ['brand_id', 'abc', 'La marca seleccionada no es válida.'],
            'marca desbordada' => ['brand_id', '99999999999999999999', 'La marca seleccionada no es válida.'],
            'marca como array' => ['brand_id', [1], 'La marca seleccionada no es válida.'],
            'marca decimal' => ['brand_id', '1.5', 'La marca seleccionada no es válida.'],
            'marca cero' => ['brand_id', '0', 'La marca seleccionada no es válida.'],
            'marca negativa' => ['brand_id', '-1', 'La marca seleccionada no es válida.'],
            'marca inexistente' => ['brand_id', '999999', 'La marca seleccionada no existe.'],
            'categoría obligatoria' => ['category_id', '', 'La categoría es obligatoria.'],
            'categoría con texto' => ['category_id', 'abc', 'La categoría seleccionada no es válida.'],
            'categoría desbordada' => ['category_id', '99999999999999999999', 'La categoría seleccionada no es válida.'],
            'categoría como array' => ['category_id', [1], 'La categoría seleccionada no es válida.'],
            'categoría decimal' => ['category_id', '1.5', 'La categoría seleccionada no es válida.'],
            'categoría cero' => ['category_id', '0', 'La categoría seleccionada no es válida.'],
            'categoría negativa' => ['category_id', '-1', 'La categoría seleccionada no es válida.'],
            'categoría inexistente' => ['category_id', '999999', 'La categoría seleccionada no existe.'],
            'nombre obligatorio' => ['name', '', 'El nombre del sabor es obligatorio.'],
            'nombre como array' => ['name', ['x'], 'El nombre del sabor debe ser un texto.'],
            'nombre largo' => ['name', str_repeat('a', 181), 'El nombre del sabor no puede superar 180 caracteres.'],
            'descripción como array' => ['description', ['x'], 'La descripción debe ser un texto.'],
            'ingredientes como array' => ['ingredients_text', ['x'], 'Los ingredientes deben ser un texto.'],
            'ingredientes largos' => ['ingredients_text', str_repeat('a', 501), 'Los ingredientes no pueden superar 500 caracteres.'],
            'público no booleano' => ['is_public', 'quizás', 'El campo público debe ser sí o no.'],
            'imagen como texto' => ['image', 'no-soy-un-archivo', 'El archivo debe ser una imagen.'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_every_field_rule_has_its_spanish_message_when_creating(string $field, mixed $value, string $message): void
    {
        $before = Flavor::count();

        $this->from(route('admin.flavors.create'))
            ->post(route('admin.flavors.store'), $this->payload([$field => $value]))
            ->assertRedirect(route('admin.flavors.create'))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertSame($before, Flavor::count());
    }

    #[DataProvider('invalidFields')]
    public function test_every_field_rule_has_its_spanish_message_when_updating(string $field, mixed $value, string $message): void
    {
        $flavor = Flavor::factory()->create(['name' => 'Intacto', 'brand_id' => $this->brand->id]);
        $before = $flavor->fresh()->toArray();

        $this->from(route('admin.flavors.edit', $flavor))
            ->put(route('admin.flavors.update', $flavor), $this->payload([$field => $value]))
            ->assertRedirect(route('admin.flavors.edit', $flavor))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertSame($before, $flavor->fresh()->toArray());
    }

    #[DataProvider('invalidFields')]
    public function test_the_create_form_shows_the_error_without_a_server_error(string $field, mixed $value, string $message): void
    {
        $this->skipKnownFormError();

        $response = $this->followingRedirects()
            ->from(route('admin.flavors.create'))
            ->post(route('admin.flavors.store'), $this->payload([$field => $value]));

        $response->assertOk()->assertSee($message)->assertDontSee('validation.');
    }

    #[DataProvider('invalidFields')]
    public function test_the_edit_form_shows_the_error_without_a_server_error(string $field, mixed $value, string $message): void
    {
        $this->skipKnownFormError();

        $flavor = Flavor::factory()->create();

        $response = $this->followingRedirects()
            ->from(route('admin.flavors.edit', $flavor))
            ->put(route('admin.flavors.update', $flavor), $this->payload([$field => $value]));

        $response->assertOk()->assertSee($message)->assertDontSee('validation.');
    }

    public function test_values_at_the_limit_are_accepted(): void
    {
        $name = str_repeat('a', 180);
        $ingredients = str_repeat('b', 500);

        $this->post(route('admin.flavors.store'), $this->payload(['name' => $name, 'ingredients_text' => $ingredients]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('flavors', ['name' => $name, 'ingredients_text' => $ingredients]);
    }

    // RF-55

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidFormIds(): array
    {
        return [
            'marca con texto' => ['brand_id', 'abc'],
            'marca desbordada' => ['brand_id', '99999999999999999999'],
            'categoría con texto' => ['category_id', 'abc'],
            'categoría desbordada' => ['category_id', '99999999999999999999'],
        ];
    }

    #[DataProvider('invalidFormIds')]
    public function test_an_invalid_id_in_the_form_does_not_reach_a_query(string $field, string $value): void
    {
        $bindings = [];
        DB::listen(function ($query) use (&$bindings) {
            $bindings = array_merge($bindings, $query->bindings);
        });

        $this->post(route('admin.flavors.store'), $this->payload([$field => $value]))->assertSessionHasErrors($field);

        $this->assertNotContains($value, $bindings);
    }

    // RF-47

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function acceptedImages(): array
    {
        return [
            'jpg' => ['foto.jpg', 'image/jpeg'],
            'jpeg' => ['foto.jpeg', 'image/jpeg'],
            'png' => ['foto.png', 'image/png'],
            'webp' => ['foto.webp', 'image/webp'],
        ];
    }

    #[DataProvider('acceptedImages')]
    public function test_every_allowed_image_format_is_accepted(string $name, string $mime): void
    {
        $this->post(route('admin.flavors.store'), $this->payload([
            'image' => UploadedFile::fake()->create($name, 10, $mime),
        ]))->assertSessionHasNoErrors();

        $this->assertCount(1, $this->storedFiles());
    }

    public function test_an_image_of_exactly_2048_kb_is_accepted(): void
    {
        $this->post(route('admin.flavors.store'), $this->payload([
            'image' => UploadedFile::fake()->create('foto.jpg', 2048, 'image/jpeg'),
        ]))->assertSessionHasNoErrors();

        $this->assertCount(1, $this->storedFiles());
    }

    public function test_an_image_of_2049_kb_is_rejected(): void
    {
        $this->post(route('admin.flavors.store'), $this->payload([
            'image' => UploadedFile::fake()->create('foto.jpg', 2049, 'image/jpeg'),
        ]))->assertSessionHasErrors(['image' => 'La imagen no puede superar 2048 KB.']);

        $this->assertSame([], $this->storedFiles());
        $this->assertDatabaseMissing('flavors', ['name' => 'Menta fresca']);
    }

    public function test_a_pdf_is_rejected(): void
    {
        $this->post(route('admin.flavors.store'), $this->payload([
            'image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors(['image' => 'El archivo debe ser una imagen.']);

        $this->assertSame([], $this->storedFiles());
    }

    public function test_an_image_in_a_format_outside_the_list_is_rejected(): void
    {
        $this->post(route('admin.flavors.store'), $this->payload([
            'image' => UploadedFile::fake()->create('foto.gif', 10, 'image/gif'),
        ]))->assertSessionHasErrors(['image' => 'La imagen debe ser jpg, jpeg, png o webp.']);

        $this->assertSame([], $this->storedFiles());
    }

    public function test_a_text_file_renamed_to_jpg_is_rejected_by_its_real_content(): void
    {
        $this->post(route('admin.flavors.store'), $this->payload([
            'image' => $this->realFile('esto no es una imagen', 'foto.jpg', 'image/jpeg'),
        ]))->assertSessionHasErrors(['image' => 'El archivo debe ser una imagen.']);

        $this->assertSame([], $this->storedFiles());
        $this->assertDatabaseMissing('flavors', ['name' => 'Menta fresca']);
    }

    public function test_an_invalid_image_on_update_leaves_the_flavor_and_its_image_intact(): void
    {
        $flavor = Flavor::factory()->create(['image_url' => 'flavors/anterior.png', 'name' => 'Intacto']);

        $this->put(route('admin.flavors.update', $flavor), $this->payload([
            'image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors('image');

        $flavor->refresh();
        $this->assertSame('flavors/anterior.png', $flavor->image_url);
        $this->assertSame('Intacto', $flavor->name);
        $this->assertSame([], $this->storedFiles());
    }

    // RF-48

    public function test_a_real_png_is_stored_on_the_public_disk_with_a_relative_path(): void
    {
        $this->post(route('admin.flavors.store'), $this->payload(['image' => $this->realPng()]))
            ->assertSessionHasNoErrors();

        $path = Flavor::where('name', 'Menta fresca')->value('image_url');

        $this->assertMatchesRegularExpression('#^flavors/[^/]+\.png$#', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(base64_decode(self::PNG_BASE64), Storage::disk('public')->get($path));
    }

    public function test_editing_without_a_new_image_keeps_the_previous_one(): void
    {
        $flavor = Flavor::factory()->create(['image_url' => 'flavors/anterior.png']);

        $this->put(route('admin.flavors.update', $flavor), $this->payload())->assertSessionHasNoErrors();

        $this->assertSame('flavors/anterior.png', $flavor->fresh()->image_url);
    }

    public function test_editing_with_a_new_image_replaces_the_path_without_deleting_the_previous_file(): void
    {
        Storage::disk('public')->put('flavors/anterior.png', 'contenido');
        $flavor = Flavor::factory()->create(['image_url' => 'flavors/anterior.png']);

        $this->put(route('admin.flavors.update', $flavor), $this->payload(['image' => $this->realPng()]))
            ->assertSessionHasNoErrors();

        $path = $flavor->fresh()->image_url;

        $this->assertNotSame('flavors/anterior.png', $path);
        $this->assertStringStartsWith('flavors/', $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists('flavors/anterior.png');
    }

    // RF-49

    public function test_a_flavor_used_in_a_mix_is_not_deleted_and_the_reason_is_shown(): void
    {
        $flavor = Flavor::factory()->create(['name' => 'En uso']);
        $mix = Mix::factory()->create();
        MixItem::create(['mix_id' => $mix->id, 'flavor_id' => $flavor->id, 'ratio' => 100]);
        Favorite::create(['user_id' => $this->admin->id, 'flavor_id' => $flavor->id]);

        $response = $this->followingRedirects()->delete(route('admin.flavors.destroy', $flavor));

        $response->assertOk()
            ->assertSee('No se puede eliminar el sabor porque se usa en alguna mezcla.')
            ->assertSee('En uso');

        $this->assertDatabaseHas('flavors', ['id' => $flavor->id]);
        $this->assertDatabaseHas('mix_items', ['mix_id' => $mix->id, 'flavor_id' => $flavor->id]);
        $this->assertDatabaseHas('favorites', ['user_id' => $this->admin->id, 'flavor_id' => $flavor->id]);
    }

    // RF-64

    public function test_a_file_rejected_by_php_does_not_save_the_flavor_and_shows_a_spanish_error(): void
    {
        $upload = fn () => $this->realFile('contenido', 'foto.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE);
        $message = 'No se ha podido subir la imagen: puede que supere el tamaño máximo permitido por el servidor.';

        $this->from(route('admin.flavors.create'))
            ->post(route('admin.flavors.store'), $this->payload(['image' => $upload()]))
            ->assertRedirect(route('admin.flavors.create'))
            ->assertSessionHasErrors(['image' => $message]);

        $this->assertDatabaseMissing('flavors', ['name' => 'Menta fresca']);
        $this->assertSame([], $this->storedFiles());

        $this->get(route('admin.flavors.create'))->assertOk()->assertSee($message);
    }

    // RF-68

    private function assertPublicCheckbox(string $html, bool $checked): void
    {
        $this->assertSame(1, preg_match('/<input\b[^>]*\bname="is_public"[^>]*>/', $html, $matches));
        $this->assertSame($checked, (bool) preg_match('/\schecked\b/', $matches[0]), $matches[0]);
    }

    public function test_a_rejected_edit_keeps_the_public_checkbox_as_it_was_sent(): void
    {
        $flavor = Flavor::factory()->create(['is_public' => true]);
        $payload = $this->payload(['name' => '']);
        unset($payload['is_public']);

        $response = $this->followingRedirects()
            ->from(route('admin.flavors.edit', $flavor))
            ->put(route('admin.flavors.update', $flavor), $payload);

        $response->assertOk()->assertSee('El nombre del sabor es obligatorio.');
        $this->assertPublicCheckbox($response->getContent(), false);
        $this->assertTrue($flavor->fresh()->is_public);
    }

    public function test_a_rejected_edit_keeps_the_public_checkbox_checked_when_it_was_sent_checked(): void
    {
        $flavor = Flavor::factory()->create(['is_public' => false]);

        $response = $this->followingRedirects()
            ->from(route('admin.flavors.edit', $flavor))
            ->put(route('admin.flavors.update', $flavor), $this->payload(['name' => '']));

        $response->assertOk()->assertSee('El nombre del sabor es obligatorio.');
        $this->assertPublicCheckbox($response->getContent(), true);
        $this->assertFalse($flavor->fresh()->is_public);
    }

    // Spec 005, RF5: editar, cancelar, guardar y eliminar vuelven a la lista con los mismos filtros, orden y página.

    private const LIST_PARAMS = ['name' => 'menta', 'brand_id' => '3', 'category_id' => '4', 'tobacco_type' => 'rub', 'visibility' => 'hidden', 'missing' => 'image', 'sort' => 'name_desc', 'page' => '2'];

    /**
     * Parámetros que no deben pasar nunca: claves fuera de la lista blanca, arrays y URL.
     *
     * @return array<string, mixed>
     */
    private function unwantedParams(): array
    {
        return ['ingredient' => 'hielo', 'direction' => 'asc', 'redirect' => 'https://evil.example', 'url' => '//evil.example', 'name_extra' => 'x', 'missing' => ['image'], 'page' => ['2']];
    }

    private function listUrl(array $params = self::LIST_PARAMS): string
    {
        return route('admin.flavors.index', $params);
    }

    private function hiddenListFields(string $html): array
    {
        preg_match_all('/<input type="hidden" name="list\[([a-z_]+)\]" value="([^"]*)">/', $html, $matches);

        return array_combine($matches[1], $matches[2]);
    }

    public function test_the_edit_and_delete_buttons_carry_the_list_parameters(): void
    {
        foreach (range(1, 51) as $number) {
            Flavor::factory()->create(['name' => sprintf('Menta %02d', $number)]);
        }
        $flavor = Flavor::where('name', 'Menta 01')->first();
        $query = ['name' => 'menta', 'sort' => 'name_desc', 'page' => '2'];

        $response = $this->get(route('admin.flavors.index', $query + ['ingredient' => 'hielo', 'direction' => 'asc']))->assertOk();

        $response->assertSee(route('admin.flavors.edit', ['flavor' => $flavor] + $query));
        preg_match_all('/href="[^"]*\/edit\?[^"]*"/', $response->getContent(), $editLinks);
        $this->assertCount(1, $editLinks[0]);
        $this->assertStringNotContainsString('ingredient', $editLinks[0][0]);
        $this->assertStringNotContainsString('direction', $editLinks[0][0]);
        $this->assertSame($query, $this->hiddenListFields($response->getContent()));
    }

    public function test_without_list_parameters_the_edit_link_is_the_plain_one(): void
    {
        $flavor = Flavor::factory()->create();

        $html = $this->get(route('admin.flavors.index'))->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('admin.flavors.edit', $flavor) . '"', $html);
        $this->assertSame([], $this->hiddenListFields($html));
    }

    public function test_the_edit_form_keeps_the_list_parameters_for_cancel_and_save(): void
    {
        $flavor = Flavor::factory()->create();

        $response = $this->get(route('admin.flavors.edit', ['flavor' => $flavor] + self::LIST_PARAMS))->assertOk();

        $response->assertSee('href="' . e($this->listUrl()) . '"', false);
        $this->assertSame(self::LIST_PARAMS, $this->hiddenListFields($response->getContent()));
    }

    public function test_the_edit_form_discards_parameters_that_are_not_allowed(): void
    {
        $flavor = Flavor::factory()->create();
        $query = ['name' => 'menta', 'sort' => 'id_asc'] + $this->unwantedParams();

        $response = $this->get(route('admin.flavors.edit', ['flavor' => $flavor] + $query))->assertOk();

        $response->assertSee('href="' . e($this->listUrl(['name' => 'menta', 'sort' => 'id_asc'])) . '"', false);
        $response->assertDontSee('evil.example', false);
        $this->assertSame(['name' => 'menta', 'sort' => 'id_asc'], $this->hiddenListFields($response->getContent()));
    }

    public function test_cancel_without_list_parameters_goes_to_the_plain_list(): void
    {
        $flavor = Flavor::factory()->create();

        $html = $this->get(route('admin.flavors.edit', $flavor))->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('admin.flavors.index') . '"', $html);
        $this->assertSame([], $this->hiddenListFields($html));
    }

    public function test_saving_goes_back_to_the_list_with_its_parameters_and_does_not_store_them(): void
    {
        $flavor = Flavor::factory()->create(['name' => 'Antiguo', 'is_public' => true]);
        $payload = $this->payload(['name' => 'Renovado']);
        unset($payload['is_public']);

        $this->put(route('admin.flavors.update', $flavor), $payload + ['list' => self::LIST_PARAMS])
            ->assertRedirect($this->listUrl())
            ->assertSessionHas('success', 'Sabor actualizado correctamente.');

        $flavor->refresh();
        $this->assertSame('Renovado', $flavor->name);
        $this->assertSame('rubio', $flavor->tobacco_type);
        $this->assertSame($this->brand->id, $flavor->brand_id);
        $this->assertFalse($flavor->is_public);
    }

    public function test_saving_discards_list_parameters_that_are_not_allowed(): void
    {
        $flavor = Flavor::factory()->create();

        $this->put(route('admin.flavors.update', $flavor), $this->payload() + ['list' => ['visibility' => 'public'] + $this->unwantedParams()])
            ->assertRedirect($this->listUrl(['visibility' => 'public']));

        $this->put(route('admin.flavors.update', $flavor), $this->payload() + ['list' => 'https://evil.example'])
            ->assertRedirect(route('admin.flavors.index'));

        $this->put(route('admin.flavors.update', $flavor), $this->payload(), ['HTTP_REFERER' => 'https://evil.example'])
            ->assertRedirect(route('admin.flavors.index'));
    }

    public function test_a_rejected_save_keeps_the_list_parameters_and_then_goes_back_with_them(): void
    {
        $flavor = Flavor::factory()->create(['name' => 'Antiguo']);
        $editUrl = route('admin.flavors.edit', ['flavor' => $flavor] + self::LIST_PARAMS);

        $response = $this->followingRedirects()
            ->from($editUrl)
            ->put(route('admin.flavors.update', $flavor), $this->payload(['name' => '']) + ['list' => self::LIST_PARAMS]);

        $response->assertOk()->assertSee('El nombre del sabor es obligatorio.');
        $response->assertSee('href="' . e($this->listUrl()) . '"', false);
        $this->assertSame(self::LIST_PARAMS, $this->hiddenListFields($response->getContent()));
        $this->assertSame('Antiguo', $flavor->fresh()->name);

        $this->put(route('admin.flavors.update', $flavor), $this->payload() + ['list' => self::LIST_PARAMS])
            ->assertRedirect($this->listUrl());
    }

    public function test_deleting_goes_back_to_the_list_with_its_parameters(): void
    {
        $flavor = Flavor::factory()->create();

        $this->delete(route('admin.flavors.destroy', $flavor), ['list' => $this->unwantedParams() + self::LIST_PARAMS])
            ->assertRedirect($this->listUrl(['name' => 'menta', 'brand_id' => '3', 'category_id' => '4', 'tobacco_type' => 'rub', 'visibility' => 'hidden', 'sort' => 'name_desc']))
            ->assertSessionHas('success', 'Sabor eliminado con éxito.');

        $this->assertDatabaseMissing('flavors', ['id' => $flavor->id]);
    }

    public function test_a_blocked_delete_goes_back_to_the_list_with_its_parameters(): void
    {
        $flavor = Flavor::factory()->create();
        $mix = Mix::factory()->create();
        MixItem::create(['mix_id' => $mix->id, 'flavor_id' => $flavor->id, 'ratio' => 100]);

        $this->delete(route('admin.flavors.destroy', $flavor), ['list' => self::LIST_PARAMS])
            ->assertRedirect($this->listUrl())
            ->assertSessionHas('error', 'No se puede eliminar el sabor porque se usa en alguna mezcla.');

        $this->assertDatabaseHas('flavors', ['id' => $flavor->id]);
    }

    public function test_deleting_the_only_flavor_of_the_last_page_ends_on_the_previous_page(): void
    {
        foreach (range(1, 51) as $number) {
            Flavor::factory()->create(['name' => sprintf('Menta %02d', $number)]);
        }
        $list = ['name' => 'menta', 'sort' => 'name_asc', 'page' => '2'];

        $this->delete(route('admin.flavors.destroy', Flavor::where('name', 'Menta 51')->first()), ['list' => $list])
            ->assertRedirect($this->listUrl($list));

        $this->get($this->listUrl($list))
            ->assertRedirect($this->listUrl(['name' => 'menta', 'sort' => 'name_asc', 'page' => 1]));
    }

    public function test_deleting_the_only_flavor_of_the_last_page_still_shows_the_message(): void
    {
        foreach (range(1, 51) as $number) {
            Flavor::factory()->create(['name' => sprintf('Menta %02d', $number)]);
        }
        $list = ['sort' => 'name_asc', 'page' => '2'];

        $this->followingRedirects()
            ->delete(route('admin.flavors.destroy', Flavor::where('name', 'Menta 51')->first()), ['list' => $list])
            ->assertOk()
            ->assertSee('Sabor eliminado con éxito.');
    }

    public function test_a_blocked_delete_from_a_page_out_of_range_still_shows_the_reason(): void
    {
        $flavor = Flavor::factory()->create();
        $mix = Mix::factory()->create();
        MixItem::create(['mix_id' => $mix->id, 'flavor_id' => $flavor->id, 'ratio' => 100]);

        $this->followingRedirects()
            ->delete(route('admin.flavors.destroy', $flavor), ['list' => ['page' => '5']])
            ->assertOk()
            ->assertSee('No se puede eliminar el sabor porque se usa en alguna mezcla.');
    }

    public function test_creating_goes_back_to_the_plain_list(): void
    {
        $this->post(route('admin.flavors.store'), $this->payload() + ['list' => self::LIST_PARAMS])
            ->assertRedirect(route('admin.flavors.index'));
    }

    public function test_the_edit_form_without_errors_shows_the_saved_public_value(): void
    {
        $public = Flavor::factory()->create(['is_public' => true]);
        $hidden = Flavor::factory()->create(['is_public' => false]);

        $this->assertPublicCheckbox($this->get(route('admin.flavors.edit', $public))->assertOk()->getContent(), true);
        $this->assertPublicCheckbox($this->get(route('admin.flavors.edit', $hidden))->assertOk()->getContent(), false);
    }
}
