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

    // RF-44

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<int, string>}>
     */
    public static function sortCases(): array
    {
        return [
            'id asc' => [['sort' => 'id', 'direction' => 'asc'], ['Menta', 'Anís', 'Coco']],
            'id desc' => [['sort' => 'id', 'direction' => 'desc'], ['Coco', 'Anís', 'Menta']],
            'nombre asc' => [['sort' => 'name', 'direction' => 'asc'], ['Anís', 'Coco', 'Menta']],
            'nombre desc' => [['sort' => 'name', 'direction' => 'desc'], ['Menta', 'Coco', 'Anís']],
            'marca asc' => [['sort' => 'brand', 'direction' => 'asc'], ['Anís', 'Menta', 'Coco']],
            'marca desc' => [['sort' => 'brand', 'direction' => 'desc'], ['Coco', 'Menta', 'Anís']],
            'por defecto' => [[], ['Coco', 'Anís', 'Menta']],
            'criterio no permitido' => [['sort' => 'created_by', 'direction' => 'asc'], ['Menta', 'Anís', 'Coco']],
            'dirección no permitida' => [['sort' => 'name', 'direction' => 'sideways'], ['Menta', 'Coco', 'Anís']],
            'criterio como array' => [['sort' => ['name'], 'direction' => ['asc']], ['Coco', 'Anís', 'Menta']],
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<int, string>  $expected
     */
    #[DataProvider('sortCases')]
    public function test_flavor_list_can_be_sorted(array $query, array $expected): void
    {
        $brandA = Brand::factory()->create(['name' => 'Alfa']);
        $brandB = Brand::factory()->create(['name' => 'Beta']);
        $brandC = Brand::factory()->create(['name' => 'Gamma']);

        // Por id: Menta, Anís, Coco. Por marca: Anís (Alfa), Menta (Beta), Coco (Gamma).
        Flavor::factory()->create(['name' => 'Menta', 'brand_id' => $brandB->id]);
        Flavor::factory()->create(['name' => 'Anís', 'brand_id' => $brandA->id]);
        Flavor::factory()->create(['name' => 'Coco', 'brand_id' => $brandC->id]);

        $this->get(route('admin.flavors.index', $query))
            ->assertOk()
            ->assertSeeInOrder($expected)
            ->assertViewHas('flavors', fn ($flavors) => $flavors->pluck('name')->all() === $expected);
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

    public function test_the_edit_form_without_errors_shows_the_saved_public_value(): void
    {
        $public = Flavor::factory()->create(['is_public' => true]);
        $hidden = Flavor::factory()->create(['is_public' => false]);

        $this->assertPublicCheckbox($this->get(route('admin.flavors.edit', $public))->assertOk()->getContent(), true);
        $this->assertPublicCheckbox($this->get(route('admin.flavors.edit', $hidden))->assertOk()->getContent(), false);
    }
}
