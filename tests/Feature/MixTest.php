<?php

namespace Tests\Feature;

use App\Http\Requests\StoreMixRequest;
use App\Models\Favorite;
use App\Models\Flavor;
use App\Models\Mix;
use App\Models\MixItem;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class MixTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Flavor $first;

    private Flavor $second;

    private Flavor $notFavorite;

    protected function setUp(): void
    {
        parent::setUp();

        // RF-60: los mensajes no dependen del idioma de la aplicación
        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $this->user = User::factory()->create();
        $this->first = Flavor::factory()->create(['name' => 'Arándano']);
        $this->second = Flavor::factory()->create(['name' => 'Menta']);
        $this->notFavorite = Flavor::factory()->create(['name' => 'Uva']);

        $this->addFavorite($this->first);
        $this->addFavorite($this->second);

        $this->actingAs($this->user);
    }

    private function addFavorite(Flavor $flavor): void
    {
        Favorite::create(['user_id' => $this->user->id, 'flavor_id' => $flavor->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mi mezcla',
            'notes' => 'Con hielo',
            'flavors' => [$this->first->id, $this->second->id],
            'ratios' => [60, 40],
        ], $overrides);
    }

    /**
     * @param  array<int, int>  $ratiosByFlavor  id del sabor => porcentaje
     */
    private function savedMix(array $ratiosByFlavor = [], array $attributes = []): Mix
    {
        $mix = Mix::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'name' => 'Mezcla guardada',
            'notes' => 'Notas guardadas',
            'is_public' => true,
        ], $attributes));

        $ratiosByFlavor = $ratiosByFlavor ?: [$this->first->id => 50, $this->second->id => 50];

        foreach ($ratiosByFlavor as $flavorId => $ratio) {
            MixItem::create(['mix_id' => $mix->id, 'flavor_id' => $flavorId, 'ratio' => $ratio]);
        }

        return $mix;
    }

    private function snapshot(Mix $mix): array
    {
        return [
            'mix' => Mix::findOrFail($mix->id)->only(['user_id', 'name', 'notes', 'is_public']),
            'items' => MixItem::where('mix_id', $mix->id)
                ->orderBy('id')
                ->get(['id', 'flavor_id', 'ratio'])
                ->toArray(),
        ];
    }

    private function savedRatios(Mix $mix): array
    {
        return MixItem::where('mix_id', $mix->id)
            ->orderBy('flavor_id')
            ->pluck('ratio', 'flavor_id')
            ->all();
    }

    private function submitCreate(array $payload)
    {
        return $this->from(route('mixes.create'))
            ->followingRedirects()
            ->post(route('mixes.store'), $payload);
    }

    private function submitEdit(Mix $mix, array $payload)
    {
        return $this->from(route('mixes.edit', $mix))
            ->followingRedirects()
            ->put(route('mixes.update', $mix), $payload);
    }

    /**
     * Filas visibles del formulario, cada una como [id del sabor elegido o null, porcentaje].
     *
     * @return array<int, array{0: ?int, 1: string}>
     */
    private function formRows(string $html): array
    {
        $this->assertSame(1, preg_match('/id="flavor-list"(.*?)id="add-row"/s', $html, $section));
        preg_match_all('/<select name="flavors\[\]".*?<\/select>/s', $section[1], $selects);
        preg_match_all('/name="ratios\[\]"[^>]*value="([^"]*)"/', $section[1], $ratios);
        $this->assertSameSize($selects[0], $ratios[1]);

        $rows = [];
        foreach ($selects[0] as $index => $select) {
            $selected = preg_match('/<option value="(\d+)"\s+selected/', $select, $match) ? (int) $match[1] : null;
            $rows[] = [$selected, $ratios[1][$index]];
        }

        return $rows;
    }

    private function newRowTemplate(string $html): string
    {
        $this->assertSame(1, preg_match('/<template id="flavor-row-template">(.*?)<\/template>/s', $html, $match));

        return $match[1];
    }

    private function assertCheckboxState(string $html, bool $checked): void
    {
        $this->assertSame(1, preg_match('/<input type="checkbox" name="is_public"[^>]*>/', $html, $match));
        $this->assertSame($checked, str_contains($match[0], 'checked'));
    }

    // RF-20
    public function test_it_creates_a_valid_mix_with_all_its_data(): void
    {
        $response = $this->submitCreate($this->payload(['is_public' => '1']));

        $response->assertOk();
        $response->assertSee('Mezcla creada correctamente.');

        $mix = Mix::sole();
        $this->assertSame($this->user->id, $mix->user_id);
        $this->assertSame('Mi mezcla', $mix->name);
        $this->assertSame('Con hielo', $mix->notes);
        $this->assertTrue((bool) $mix->is_public);
        $this->assertEquals(
            [$this->first->id => 60, $this->second->id => 40],
            $this->savedRatios($mix)
        );
    }

    public function test_a_mix_without_the_public_checkbox_is_saved_as_private(): void
    {
        $this->submitCreate($this->payload())->assertOk();

        $this->assertFalse((bool) Mix::sole()->is_public);
    }

    public function test_a_single_flavor_at_100_percent_is_valid(): void
    {
        $response = $this->submitCreate($this->payload([
            'flavors' => [$this->first->id],
            'ratios' => [100],
        ]));

        $response->assertSee('Mezcla creada correctamente.');
        $this->assertEquals([$this->first->id => 100], $this->savedRatios(Mix::sole()));
    }

    // RF-58: el límite exacto se acepta
    public function test_a_name_of_exactly_180_characters_is_accepted(): void
    {
        $response = $this->submitCreate($this->payload(['name' => str_repeat('ñ', 180)]));

        $response->assertSee('Mezcla creada correctamente.');
        $this->assertSame(1, Mix::count());
    }

    /**
     * RF-21, 22, 23, 24, 26, 28, 54, 55, 58 y 60: cada regla, con su mensaje literal.
     */
    public static function invalidMixes(): array
    {
        $sum = 'La suma de los porcentajes debe ser exactamente 100%.';
        $position = 'Cada sabor debe tener su porcentaje en la misma posición.';
        $invalidFlavor = 'El sabor seleccionado no es válido.';
        $ratioInteger = 'Cada porcentaje debe ser un número entero.';
        $ratioMin = 'Cada porcentaje debe ser al menos 1.';

        return [
            'nombre vacío' => [fn ($t) => ['name' => ''], 'El nombre de la mezcla es obligatorio.'],
            'nombre que no es texto' => [fn ($t) => ['name' => ['x']], 'El nombre de la mezcla debe ser un texto.'],
            'nombre de 181 caracteres' => [fn ($t) => ['name' => str_repeat('a', 181)], 'El nombre de la mezcla no puede superar los 180 caracteres.'],
            'notas que no son texto' => [fn ($t) => ['notes' => ['x']], 'Las notas deben ser un texto.'],

            'sin sabores' => [fn ($t) => ['flavors' => null], 'Debes añadir al menos un sabor.'],
            'lista de sabores vacía' => [fn ($t) => ['flavors' => []], 'Debes añadir al menos un sabor.'],
            'sabores que no son lista' => [fn ($t) => ['flavors' => 'abc'], 'Los sabores deben enviarse como una lista.'],
            'sabor repetido' => [fn ($t) => ['flavors' => [$t->first->id, $t->first->id], 'ratios' => [50, 50]], 'No puedes repetir un sabor en la misma mezcla.'],
            'más sabores que porcentajes' => [fn ($t) => ['ratios' => [100]], $position],
            'más porcentajes que sabores' => [fn ($t) => ['ratios' => [50, 30, 20]], $position],
            'sabor y porcentaje en posiciones distintas' => [fn ($t) => ['flavors' => [0 => $t->first->id], 'ratios' => [5 => 100]], $position],
            'sabor fuera de favoritos' => [fn ($t) => ['flavors' => [$t->first->id, $t->notFavorite->id]], 'Solo puedes añadir sabores de tus favoritos.'],

            'fila sin sabor' => [fn ($t) => ['flavors' => [null], 'ratios' => [100]], 'Selecciona un sabor en cada fila.'],
            'id de sabor con texto' => [fn ($t) => ['flavors' => ['abc'], 'ratios' => [100]], $invalidFlavor],
            'id de sabor desbordado' => [fn ($t) => ['flavors' => ['99999999999999999999'], 'ratios' => [100]], $invalidFlavor],
            'id de sabor decimal' => [fn ($t) => ['flavors' => ['1.5'], 'ratios' => [100]], $invalidFlavor],
            'id de sabor en array' => [fn ($t) => ['flavors' => [[1]], 'ratios' => [100]], $invalidFlavor],
            'id de sabor cero' => [fn ($t) => ['flavors' => ['0'], 'ratios' => [100]], $invalidFlavor],
            'id de sabor negativo' => [fn ($t) => ['flavors' => ['-1'], 'ratios' => [100]], $invalidFlavor],
            'sabor inexistente' => [fn ($t) => ['flavors' => ['999999'], 'ratios' => [100]], 'El sabor seleccionado no existe.'],

            'sin porcentajes' => [fn ($t) => ['ratios' => null], 'Debes indicar los porcentajes.'],
            'lista de porcentajes vacía' => [fn ($t) => ['ratios' => []], 'Debes indicar los porcentajes.'],
            'porcentajes que no son lista' => [fn ($t) => ['ratios' => 'abc'], 'Los porcentajes deben enviarse como una lista.'],
            'suma 99' => [fn ($t) => ['ratios' => [60, 39]], $sum],
            'suma 101' => [fn ($t) => ['ratios' => [60, 41]], $sum],

            'fila sin porcentaje' => [fn ($t) => ['flavors' => [$t->first->id], 'ratios' => [null]], 'Indica el porcentaje de cada sabor.'],
            'porcentaje con texto' => [fn ($t) => ['flavors' => [$t->first->id], 'ratios' => ['abc']], $ratioInteger],
            'porcentaje 50.5' => [fn ($t) => ['ratios' => ['50.5', '49.5']], $ratioInteger],
            'porcentaje en array' => [fn ($t) => ['flavors' => [$t->first->id], 'ratios' => [[100]]], $ratioInteger],
            'porcentaje 0' => [fn ($t) => ['ratios' => [0, 100]], $ratioMin],
            'porcentaje negativo' => [fn ($t) => ['ratios' => [-10, 110]], $ratioMin],
            'porcentaje 101' => [fn ($t) => ['ratios' => [101, -1]], 'Cada porcentaje no puede superar 100.'],
        ];
    }

    #[DataProvider('invalidMixes')]
    public function test_creating_an_invalid_mix_saves_nothing_and_shows_the_error_in_spanish(Closure $overrides, string $message): void
    {
        $response = $this->submitCreate($this->payload($overrides($this)));

        $response->assertOk();
        $response->assertSee($message);
        $response->assertDontSee('validation.');
        $this->assertSame(0, Mix::count());
        $this->assertSame(0, MixItem::count());
    }

    #[DataProvider('invalidMixes')]
    public function test_editing_with_invalid_data_keeps_the_mix_as_it_was_and_shows_the_same_error(Closure $overrides, string $message): void
    {
        $mix = $this->savedMix();
        $before = $this->snapshot($mix);

        $response = $this->submitEdit($mix, $this->payload($overrides($this)));

        $response->assertOk();
        $response->assertSee($message);
        $response->assertDontSee('validation.');
        $this->assertSame($before, $this->snapshot($mix));
    }

    // RF-54: con `required` delante, una lista vacía nunca llega a `min:1`, pero la regla tiene su mensaje
    public function test_the_min_rules_of_the_lists_have_their_own_message(): void
    {
        $messages = (new StoreMixRequest)->messages();

        $this->assertSame('Debes añadir al menos un sabor.', $messages['flavors.min']);
        $this->assertSame('Debes indicar los porcentajes.', $messages['ratios.min']);
        $this->assertSame('Los porcentajes deben enviarse como una lista.', $messages['ratios.array']);
    }

    // RF-55: el id no válido de un formulario no llega a la consulta (`exists` fallaría en Postgres)
    public static function invalidFlavorIds(): array
    {
        return [
            'texto' => ['abc'],
            'desbordado' => ['99999999999999999999'],
            'decimal' => ['1.5'],
            'negativo' => ['-1'],
        ];
    }

    #[DataProvider('invalidFlavorIds')]
    public function test_an_invalid_flavor_id_does_not_reach_the_database(string $id): void
    {
        $mix = $this->savedMix();

        $bindings = [];
        DB::listen(function ($query) use (&$bindings) {
            array_push($bindings, ...array_map('strval', $query->bindings));
        });

        $payload = $this->payload(['flavors' => [$id], 'ratios' => [100]]);

        $this->from(route('mixes.create'))->post(route('mixes.store'), $payload)->assertSessionHasErrors('flavors.0');
        $this->from(route('mixes.edit', $mix))->put(route('mixes.update', $mix), $payload)->assertSessionHasErrors('flavors.0');

        $this->assertNotContains($id, $bindings);
    }

    // RF-24 y RF-26: el caso contrario, un sabor de favoritos sí se puede añadir al editar
    public function test_editing_can_add_a_new_flavor_from_favorites(): void
    {
        $third = Flavor::factory()->create();
        $this->addFavorite($third);
        $mix = $this->savedMix();

        $response = $this->submitEdit($mix, $this->payload([
            'flavors' => [$this->first->id, $third->id],
            'ratios' => [70, 30],
        ]));

        $response->assertSee('Mezcla actualizada correctamente.');
        $this->assertEquals([$this->first->id => 70, $third->id => 30], $this->savedRatios($mix));
    }

    // RF-26: un sabor añadido que no está en favoritos ni en la mezcla se rechaza
    public function test_editing_rejects_a_new_flavor_outside_favorites_even_with_a_valid_sum(): void
    {
        $mix = $this->savedMix();
        $before = $this->snapshot($mix);

        $response = $this->submitEdit($mix, $this->payload([
            'flavors' => [$this->first->id, $this->second->id, $this->notFavorite->id],
            'ratios' => [50, 25, 25],
        ]));

        $response->assertSee('Solo puedes añadir sabores de tus favoritos.');
        $this->assertSame($before, $this->snapshot($mix));
    }

    // RF-24: favorito de otro usuario, no vale
    public function test_a_favorite_of_another_user_cannot_be_used(): void
    {
        Favorite::create(['user_id' => User::factory()->create()->id, 'flavor_id' => $this->notFavorite->id]);

        $response = $this->submitCreate($this->payload([
            'flavors' => [$this->notFavorite->id],
            'ratios' => [100],
        ]));

        $response->assertSee('Solo puedes añadir sabores de tus favoritos.');
        $this->assertSame(0, Mix::count());
    }

    // RF-25
    public function test_a_hidden_flavor_in_favorites_is_not_blocked_when_creating(): void
    {
        $hidden = Flavor::factory()->notPublic()->create();
        $this->addFavorite($hidden);

        $this->get(route('mixes.create'))->assertSee('<option value="'.$hidden->id.'"', false);

        $response = $this->submitCreate($this->payload(['flavors' => [$hidden->id], 'ratios' => [100]]));

        $response->assertSee('Mezcla creada correctamente.');
        $this->assertEquals([$hidden->id => 100], $this->savedRatios(Mix::sole()));
    }

    public function test_a_hidden_flavor_already_present_and_outside_favorites_can_stay(): void
    {
        $hidden = Flavor::factory()->notPublic()->create();
        $mix = $this->savedMix([$hidden->id => 50, $this->first->id => 50]);

        $response = $this->submitEdit($mix, $this->payload([
            'flavors' => [$hidden->id, $this->first->id],
            'ratios' => [30, 70],
        ]));

        $response->assertSee('Mezcla actualizada correctamente.');
        $this->assertEquals([$hidden->id => 30, $this->first->id => 70], $this->savedRatios($mix));
    }

    // RF-27
    public function test_a_present_flavor_outside_favorites_is_selected_in_its_row_and_offered_in_new_rows(): void
    {
        $mix = $this->savedMix([$this->notFavorite->id => 60, $this->first->id => 40]);

        $html = $this->get(route('mixes.edit', $mix))->assertOk()->getContent();

        // El orden de las filas guardadas no está definido: solo importa que cada sabor lleve su porcentaje
        $this->assertEqualsCanonicalizing(
            [[$this->notFavorite->id, '60'], [$this->first->id, '40']],
            $this->formRows($html)
        );
        $this->assertStringContainsString('<option value="'.$this->notFavorite->id.'"', $this->newRowTemplate($html));
        $this->assertStringContainsString('<option value="'.$this->second->id.'"', $this->newRowTemplate($html));
    }

    public function test_the_edit_form_does_not_offer_flavors_outside_favorites_or_the_mix(): void
    {
        $mix = $this->savedMix();

        $html = $this->get(route('mixes.edit', $mix))->getContent();

        $this->assertStringNotContainsString('<option value="'.$this->notFavorite->id.'"', $html);
    }

    public function test_a_present_flavor_outside_favorites_can_be_saved(): void
    {
        $mix = $this->savedMix([$this->notFavorite->id => 60, $this->first->id => 40]);

        $response = $this->submitEdit($mix, $this->payload([
            'flavors' => [$this->notFavorite->id, $this->first->id],
            'ratios' => [60, 40],
        ]));

        $response->assertSee('Mezcla actualizada correctamente.');
        $this->assertEquals([$this->notFavorite->id => 60, $this->first->id => 40], $this->savedRatios($mix));
    }

    public function test_a_rejected_edit_still_offers_the_present_flavor_removed_from_its_row(): void
    {
        $mix = $this->savedMix([$this->notFavorite->id => 60, $this->first->id => 40]);

        $response = $this->submitEdit($mix, $this->payload([
            'flavors' => [$this->first->id],
            'ratios' => [99],
        ]));

        $response->assertSee('La suma de los porcentajes debe ser exactamente 100%.');
        $html = $response->getContent();
        $this->assertSame([[$this->first->id, '99']], $this->formRows($html));
        $this->assertStringContainsString('<option value="'.$this->notFavorite->id.'"', $this->newRowTemplate($html));

        $response = $this->submitEdit($mix, $this->payload([
            'flavors' => [$this->first->id, $this->notFavorite->id],
            'ratios' => [50, 50],
        ]));

        $response->assertSee('Mezcla actualizada correctamente.');
    }

    public function test_a_present_flavor_can_be_replaced_and_then_stops_being_present(): void
    {
        $mix = $this->savedMix([$this->notFavorite->id => 60, $this->first->id => 40]);

        $this->submitEdit($mix, $this->payload([
            'flavors' => [$this->second->id, $this->first->id],
            'ratios' => [60, 40],
        ]))->assertSee('Mezcla actualizada correctamente.');

        $response = $this->submitEdit($mix, $this->payload([
            'flavors' => [$this->notFavorite->id, $this->first->id],
            'ratios' => [60, 40],
        ]));

        $response->assertSee('Solo puedes añadir sabores de tus favoritos.');
        $this->assertEquals([$this->second->id => 60, $this->first->id => 40], $this->savedRatios($mix));
    }

    // Casos límite: quitar de favoritos un sabor usado no cambia la mezcla
    public function test_removing_a_favorite_does_not_change_a_saved_mix(): void
    {
        $mix = $this->savedMix();
        $before = $this->snapshot($mix);

        $this->post(route('favorites.store', $this->first));

        $this->assertSame($before, $this->snapshot($mix));
    }

    // RF-29
    private function failWhenCreatingTheSecondItem(): void
    {
        $created = 0;

        MixItem::creating(function () use (&$created) {
            if (++$created === 2) {
                throw new RuntimeException('Fallo forzado');
            }
        });
    }

    public function test_when_the_second_flavor_fails_on_create_no_mix_is_left(): void
    {
        $this->failWhenCreatingTheSecondItem();
        $this->withoutExceptionHandling();

        try {
            $this->post(route('mixes.store'), $this->payload());
            $this->fail('La excepción forzada debía propagarse.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fallo forzado', $exception->getMessage());
        }

        $this->assertSame(0, Mix::count());
        $this->assertSame(0, MixItem::count());
    }

    public function test_when_the_second_flavor_fails_on_edit_the_previous_version_stays_complete(): void
    {
        $mix = $this->savedMix();
        $before = $this->snapshot($mix);

        $this->failWhenCreatingTheSecondItem();
        $this->withoutExceptionHandling();

        try {
            $this->put(route('mixes.update', $mix), $this->payload());
            $this->fail('La excepción forzada debía propagarse.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fallo forzado', $exception->getMessage());
        }

        $this->assertSame($before, $this->snapshot($mix));
    }

    // RF-30: MySQL y Postgres devuelven `50.00`; SQLite, no. Se comprueba el cast con el valor crudo.
    public function test_a_ratio_read_as_decimal_text_is_an_integer(): void
    {
        $item = (new MixItem)->setRawAttributes(['ratio' => '50.00']);

        $this->assertSame(50, $item->ratio);
    }

    public function test_the_edit_form_and_the_list_show_integer_ratios(): void
    {
        $mix = $this->savedMix();

        $html = $this->get(route('mixes.edit', $mix))->getContent();
        $this->assertEqualsCanonicalizing([[$this->first->id, '50'], [$this->second->id, '50']], $this->formRows($html));

        $list = $this->get(route('mixes.index'));
        $list->assertSee('50%');
        $list->assertDontSee('50.00%');
    }

    public function test_saving_the_edit_form_without_touching_the_ratios_works(): void
    {
        $mix = $this->savedMix();

        $response = $this->submitEdit($mix, [
            'name' => $mix->name,
            'notes' => $mix->notes,
            'is_public' => '1',
            'flavors' => [$this->first->id, $this->second->id],
            'ratios' => ['50', '50'],
        ]);

        $response->assertSee('Mezcla actualizada correctamente.');
        $this->assertEquals([$this->first->id => 50, $this->second->id => 50], $this->savedRatios($mix));
    }

    // RF-31
    public function test_a_rejected_creation_keeps_what_the_user_wrote(): void
    {
        $response = $this->submitCreate($this->payload([
            'name' => 'Escrito por mí',
            'notes' => 'Mis notas',
            'is_public' => '1',
            'ratios' => [60, 30],
        ]));

        $response->assertSee('La suma de los porcentajes debe ser exactamente 100%.');
        $response->assertSee('value="Escrito por mí"', false);
        $response->assertSee('>Mis notas</textarea>', false);

        $html = $response->getContent();
        $this->assertSame([[$this->first->id, '60'], [$this->second->id, '30']], $this->formRows($html));
        $this->assertCheckboxState($html, true);
    }

    public function test_a_rejected_creation_keeps_the_public_checkbox_unchecked(): void
    {
        $response = $this->submitCreate($this->payload(['ratios' => [60, 30]]));

        $this->assertCheckboxState($response->getContent(), false);
    }

    public function test_the_forms_give_the_search_the_name_and_brand_of_each_flavor(): void
    {
        $mix = $this->savedMix();
        $brand = e($this->first->brand->name);

        foreach ([route('mixes.create'), route('mixes.edit', $mix)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $template = $this->newRowTemplate($html);

            $this->assertMatchesRegularExpression(
                '/<option value="'.$this->first->id.'"\s+data-name="'.preg_quote(e($this->first->name), '/').'" data-brand="'.preg_quote($brand, '/').'"/',
                $template
            );
        }
    }

    public function test_the_create_form_explains_how_to_get_flavors_when_there_are_no_favorites(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('mixes.create'))
            ->assertOk()
            ->assertSee('Todavía no tienes favoritos')
            ->assertSee('href="'.route('home').'"', false);
    }

    public function test_the_list_draws_the_composition_of_each_mix_with_its_brand(): void
    {
        $this->savedMix([$this->first->id => 70, $this->second->id => 30]);

        $this->get(route('mixes.index'))
            ->assertOk()
            ->assertSee('flex-grow: 70', false)
            ->assertSee('flex-grow: 30', false)
            ->assertSee($this->first->brand->name);
    }

    /**
     * HTML de la ventana de una mezcla: desde su id hasta la siguiente ventana o el final de <main>.
     */
    private function mixWindow(string $html, Mix $mix): string
    {
        $start = strpos($html, 'id="mixModal-'.$mix->id.'"');
        $this->assertNotFalse($start, 'La ventana de la mezcla no aparece en el listado.');

        $ends = array_filter([
            strpos($html, 'class="modal fade', $start),
            strpos($html, '</main>', $start),
        ], fn ($position) => $position !== false);
        $this->assertNotEmpty($ends);

        return substr($html, $start, min($ends) - $start);
    }

    // RF-13
    public function test_the_list_has_a_window_for_each_own_mix_with_its_data(): void
    {
        $public = $this->savedMix([$this->first->id => 70, $this->second->id => 30], ['name' => 'Mezcla de día', 'notes' => 'Con mucho hielo']);
        $private = $this->savedMix([$this->first->id => 100], ['name' => 'Mezcla de noche', 'notes' => null, 'is_public' => false]);

        $html = $this->get(route('mixes.index'))->assertOk()->getContent();

        $window = $this->mixWindow($html, $public);
        $this->assertStringContainsString('Mezcla de día', $window);
        $this->assertStringContainsString('Pública', $window);
        $this->assertStringContainsString('Con mucho hielo', $window);
        $this->assertStringContainsString('Arándano', $window);
        $this->assertStringContainsString('Menta', $window);
        $this->assertStringContainsString(e($this->first->brand->name), $window);
        $this->assertStringContainsString(e($this->second->brand->name), $window);
        $this->assertStringContainsString('70%', $window);
        $this->assertStringContainsString('30%', $window);
        $this->assertStringContainsString('flex-grow: 70', $window);
        $this->assertStringContainsString('Creada el '.$public->created_at->format('d/m/Y'), $window);
        $this->assertStringContainsString('aria-label="Cerrar"', $window);

        $window = $this->mixWindow($html, $private);
        $this->assertStringContainsString('Mezcla de noche', $window);
        $this->assertStringContainsString('Privada', $window);
        $this->assertStringContainsString('100%', $window);
        $this->assertStringNotContainsString('Pública', $window);

        // La tarjeta abre su ventana
        $this->assertStringContainsString('openMixModal('.$public->id.')', $html);
    }

    // RF-15, RF-16
    public function test_the_mix_window_has_edit_and_delete_actions(): void
    {
        $mix = $this->savedMix();

        $window = $this->mixWindow($this->get(route('mixes.index'))->assertOk()->getContent(), $mix);

        $this->assertStringContainsString('href="'.route('mixes.edit', $mix).'"', $window);
        $this->assertStringContainsString('action="'.route('mixes.destroy', $mix).'"', $window);
        $this->assertStringContainsString('name="_method" value="DELETE"', $window);
        $this->assertStringContainsString("return confirm('¿Seguro que quieres eliminar esta mezcla?')", $window);
        $this->assertStringContainsString('>Editar</a>', $window);
        $this->assertStringContainsString('>Eliminar</button>', $window);
    }

    // RF-19
    public function test_the_list_has_no_window_for_foreign_mixes(): void
    {
        $own = $this->savedMix();
        $foreign = Mix::factory()->visibleToOthers()->create(['name' => 'Mezcla ajena pública']);

        $response = $this->get(route('mixes.index'))->assertOk();

        $response->assertSee('id="mixModal-'.$own->id.'"', false);
        $response->assertDontSee('id="mixModal-'.$foreign->id.'"', false);
        $response->assertDontSee('openMixModal('.$foreign->id.')', false);
    }

    // RF-13 (caso límite)
    public function test_a_mix_without_flavors_shows_the_empty_text_in_its_window(): void
    {
        $mix = Mix::factory()->create(['user_id' => $this->user->id, 'name' => 'Mezcla vacía']);

        $window = $this->mixWindow($this->get(route('mixes.index'))->assertOk()->getContent(), $mix);

        $this->assertStringContainsString('Esta mezcla no tiene sabores.', $window);
        $this->assertStringNotContainsString('class="mix-bar', $window);
    }

    // RF-13
    public function test_a_mix_without_created_at_shows_no_date(): void
    {
        $mix = $this->savedMix();
        DB::table('mixes')->where('id', $mix->id)->update(['created_at' => null]);

        $response = $this->get(route('mixes.index'))->assertOk();

        $response->assertDontSee('Creada el');
        $this->assertStringContainsString('Mezcla guardada', $this->mixWindow($response->getContent(), $mix));
    }

    public function test_the_create_form_starts_with_one_empty_row_and_no_errors(): void
    {
        $response = $this->get(route('mixes.create'))->assertOk();

        $response->assertDontSee('alert-danger');
        $this->assertSame([[null, '']], $this->formRows($response->getContent()));
    }

    public function test_a_rejected_edit_shows_what_the_user_wrote_not_what_is_saved(): void
    {
        $third = Flavor::factory()->create();
        $this->addFavorite($third);
        $mix = $this->savedMix();

        $response = $this->submitEdit($mix, [
            'name' => 'Nombre nuevo',
            'notes' => 'Notas nuevas',
            'is_public' => '1',
            'flavors' => [$third->id, $this->first->id, $this->second->id],
            'ratios' => [10, 20, 30],
        ]);

        $response->assertSee('value="Nombre nuevo"', false);
        $response->assertSee('>Notas nuevas</textarea>', false);
        $response->assertDontSee('Notas guardadas');
        $this->assertSame(
            [[$third->id, '10'], [$this->first->id, '20'], [$this->second->id, '30']],
            $this->formRows($response->getContent())
        );
    }

    public function test_a_rejected_edit_keeps_the_public_checkbox_unchecked_on_a_public_mix(): void
    {
        $mix = $this->savedMix();

        $response = $this->submitEdit($mix, $this->payload(['ratios' => [60, 30]]));

        $response->assertSee('La suma de los porcentajes debe ser exactamente 100%.');
        $this->assertCheckboxState($response->getContent(), false);
    }

    public function test_a_rejected_edit_keeps_the_public_checkbox_checked_on_a_private_mix(): void
    {
        $mix = $this->savedMix(attributes: ['is_public' => false]);

        $response = $this->submitEdit($mix, $this->payload(['ratios' => [60, 30], 'is_public' => '1']));

        $this->assertCheckboxState($response->getContent(), true);
    }

    public function test_a_rejected_edit_keeps_empty_notes_empty(): void
    {
        $mix = $this->savedMix();

        $response = $this->submitEdit($mix, $this->payload(['notes' => '', 'ratios' => [60, 30]]));

        $response->assertSee('La suma de los porcentajes debe ser exactamente 100%.');
        $response->assertDontSee('Notas guardadas');
    }

    public function test_the_edit_form_without_errors_shows_what_is_saved(): void
    {
        $mix = $this->savedMix();

        $response = $this->get(route('mixes.edit', $mix))->assertOk();

        $response->assertSee('value="Mezcla guardada"', false);
        $response->assertSee('>Notas guardadas</textarea>', false);
        $this->assertCheckboxState($response->getContent(), true);
    }

    // RF-31: los valores que no son texto no rompen el formulario al volver a pintarlo
    public function test_a_flavor_row_with_an_array_is_painted_empty(): void
    {
        $response = $this->submitCreate($this->payload([
            'flavors' => [[1], $this->first->id],
            'ratios' => [[100], 50],
        ]));

        $response->assertOk();
        $this->assertSame([[null, ''], [$this->first->id, '50']], $this->formRows($response->getContent()));
    }

    public function test_lists_sent_as_text_paint_an_empty_row(): void
    {
        $mix = $this->savedMix();

        $created = $this->submitCreate($this->payload(['flavors' => 'abc', 'ratios' => 'abc']));
        $edited = $this->submitEdit($mix, $this->payload(['flavors' => 'abc', 'ratios' => 'abc']));

        $created->assertOk();
        $edited->assertOk();
        $this->assertSame([[null, '']], $this->formRows($created->getContent()));
        $this->assertSame([[null, '']], $this->formRows($edited->getContent()));
    }

    // RF-32
    public function test_the_list_only_shows_own_mixes_even_if_others_are_public(): void
    {
        $this->savedMix(attributes: ['name' => 'Mezcla mía']);
        Mix::factory()->visibleToOthers()->create(['name' => 'Mezcla ajena pública']);
        Mix::factory()->create(['name' => 'Mezcla ajena privada']);

        $response = $this->get(route('mixes.index'))->assertOk();

        $response->assertSee('Mezcla mía');
        $response->assertDontSee('Mezcla ajena pública');
        $response->assertDontSee('Mezcla ajena privada');
    }

    // RF-33
    public static function foreignMixes(): array
    {
        return [
            'privada' => [false],
            'pública' => [true],
        ];
    }

    #[DataProvider('foreignMixes')]
    public function test_a_foreign_mix_cannot_be_opened_edited_or_deleted(bool $public): void
    {
        $mix = $this->savedMix(attributes: ['user_id' => User::factory()->create()->id, 'is_public' => $public]);
        $before = $this->snapshot($mix);

        $this->get(route('mixes.edit', $mix))->assertForbidden();
        $this->put(route('mixes.update', $mix), $this->payload())->assertForbidden();
        $this->put(route('mixes.update', $mix), ['name' => ''])->assertForbidden();
        $this->delete(route('mixes.destroy', $mix))->assertForbidden();

        $this->assertSame($before, $this->snapshot($mix));
    }

    public function test_the_owner_can_open_edit_and_delete_their_mix(): void
    {
        $mix = $this->savedMix();

        $this->get(route('mixes.edit', $mix))->assertOk();
        $this->put(route('mixes.update', $mix), $this->payload())->assertRedirect(route('mixes.index'));
        $this->assertSame('Mi mezcla', $mix->fresh()->name);

        $this->delete(route('mixes.destroy', $mix))->assertRedirect(route('mixes.index'));
        $this->assertNull($mix->fresh());
    }

    public function test_an_admin_cannot_touch_a_foreign_mix_either(): void
    {
        $mix = $this->savedMix();
        $before = $this->snapshot($mix);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(route('mixes.edit', $mix))->assertForbidden();
        $this->put(route('mixes.update', $mix), $this->payload())->assertForbidden();
        $this->delete(route('mixes.destroy', $mix))->assertForbidden();

        $this->assertSame($before, $this->snapshot($mix));
    }

    // RF-34
    public function test_deleting_a_mix_removes_its_items_and_keeps_flavors_favorites_and_other_mixes(): void
    {
        $mix = $this->savedMix();
        $other = $this->savedMix([$this->first->id => 100], ['name' => 'Otra']);
        $otherBefore = $this->snapshot($other);

        $response = $this->followingRedirects()->delete(route('mixes.destroy', $mix));

        $response->assertSee('Mezcla eliminada correctamente.');
        $this->assertNull($mix->fresh());
        $this->assertSame(0, MixItem::where('mix_id', $mix->id)->count());
        $this->assertSame(3, Flavor::count());
        $this->assertSame(2, Favorite::where('user_id', $this->user->id)->count());
        $this->assertSame($otherBefore, $this->snapshot($other));
    }

    // RF-7 en las rutas de mezclas
    public function test_a_visitor_is_sent_to_login_and_nothing_is_saved(): void
    {
        auth()->logout();

        $this->get(route('mixes.index'))->assertRedirect(route('login'));
        $this->post(route('mixes.store'), $this->payload())->assertRedirect(route('login'));
        $this->assertSame(0, Mix::count());
    }

    /**
     * Spec 007: mezcla de otro usuario, pública por defecto.
     *
     * @param  array<int, int>  $ratiosByFlavor  id del sabor => porcentaje
     */
    private function foreignMix(string $name, array $ratiosByFlavor = [], array $attributes = []): Mix
    {
        $mix = Mix::factory()->create(array_merge(['name' => $name, 'is_public' => true], $attributes));

        foreach ($ratiosByFlavor ?: [$this->first->id => 100] as $flavorId => $ratio) {
            MixItem::create(['mix_id' => $mix->id, 'flavor_id' => $flavorId, 'ratio' => $ratio]);
        }

        return $mix;
    }

    /**
     * @return array<int, string>
     */
    private function publicNames(array $query = []): array
    {
        return $this->get(route('mixes.public', $query))
            ->assertOk()
            ->viewData('mixes')
            ->pluck('name')
            ->all();
    }

    // Spec 007, RF1 y RF2: sin sesión se ven las públicas de todos; nunca las privadas ni las que llevan un sabor oculto.
    public function test_a_guest_sees_public_mixes_with_only_public_flavors(): void
    {
        $hidden = Flavor::factory()->notPublic()->create(['name' => 'Sabor oculto']);

        $this->foreignMix('Pública de otro');
        $this->savedMix([], ['name' => 'Pública mía']);
        $this->foreignMix('Privada de otro', [], ['is_public' => false]);
        $this->foreignMix('Con sabor oculto', [$this->first->id => 50, $hidden->id => 50]);

        auth()->logout();

        $this->assertEqualsCanonicalizing(['Pública de otro', 'Pública mía'], $this->publicNames());
        $this->assertGuest();
    }

    // Spec 007, RF2: con sesión no salen las propias.
    public function test_with_a_session_own_mixes_are_not_listed(): void
    {
        $this->foreignMix('Pública de otro');
        $this->savedMix([], ['name' => 'Pública mía']);

        $this->assertSame(['Pública de otro'], $this->publicNames());
    }

    // Spec 007, RF4: nombre de la mezcla sin mayúsculas ni acentos.
    public function test_it_filters_by_mix_name_ignoring_case_and_accents(): void
    {
        $this->foreignMix('Noche Árabe');
        $this->foreignMix('Fresca de verano');

        $this->assertSame(['Noche Árabe'], $this->publicNames(['name' => 'ARABE']));
        $this->assertSame(['Fresca de verano'], $this->publicNames(['name' => 'Verano']));
    }

    // Spec 007, RF4: sabor que contiene, por nombre del sabor.
    public function test_it_filters_by_a_flavor_the_mix_contains(): void
    {
        $this->foreignMix('Con arándano', [$this->first->id => 100]);
        $this->foreignMix('Con menta', [$this->second->id => 100]);

        $this->assertSame(['Con arándano'], $this->publicNames(['flavor' => 'ARANDANO']));
    }

    // Spec 007, RF4: categoría que contiene y combinación de filtros.
    public function test_it_filters_by_category_and_combines_the_filters(): void
    {
        $citrus = \App\Models\Category::factory()->create(['name' => 'Cítrico']);
        $lemon = Flavor::factory()->create(['name' => 'Limón', 'category_id' => $citrus->id]);

        $this->foreignMix('Limonada', [$lemon->id => 50, $this->first->id => 50]);
        $this->foreignMix('Limón solo', [$lemon->id => 100]);
        $this->foreignMix('Sin cítrico', [$this->second->id => 100]);

        $this->assertEqualsCanonicalizing(['Limonada', 'Limón solo'], $this->publicNames(['category_id' => $citrus->id]));
        $this->assertSame(['Limonada'], $this->publicNames(['category_id' => $citrus->id, 'flavor' => 'arandano']));
        $this->assertSame([], $this->publicNames(['category_id' => $citrus->id, 'name' => 'sin']));
    }

    // Spec 007, RF5: los tres órdenes; por defecto, las más recientes.
    public function test_public_mixes_can_be_sorted(): void
    {
        $this->foreignMix('Beta', [], ['created_at' => now()->subDays(1)]);
        $this->foreignMix('alfa', [], ['created_at' => now()->subDays(2)]);
        $this->foreignMix('Gamma', [], ['created_at' => now()]);

        $this->assertSame(['Gamma', 'Beta', 'alfa'], $this->publicNames());
        $this->assertSame(['Gamma', 'Beta', 'alfa'], $this->publicNames(['sort' => 'recent']));
        $this->assertSame(['alfa', 'Beta', 'Gamma'], $this->publicNames(['sort' => 'name_asc']));
        $this->assertSame(['Gamma', 'Beta', 'alfa'], $this->publicNames(['sort' => 'name_desc']));
        $this->assertSame(['Gamma', 'Beta', 'alfa'], $this->publicNames(['sort' => 'id_asc']));

        // Una mezcla sin fecha va al final en cualquier motor.
        $undated = $this->foreignMix('Sin fecha');
        Mix::whereKey($undated->id)->update(['created_at' => null]);

        $this->assertSame(['Gamma', 'Beta', 'alfa', 'Sin fecha'], $this->publicNames());
    }

    // Spec 007, RF5: 12 por página, conservando filtros y orden.
    public function test_public_mixes_are_paginated_keeping_filters_and_sort(): void
    {
        foreach (range(1, 13) as $number) {
            $this->foreignMix(sprintf('Mezcla %02d', $number));
        }

        $query = ['name' => 'mezcla', 'sort' => 'name_asc'];
        $mixes = $this->get(route('mixes.public', $query))->assertOk()->viewData('mixes');

        $this->assertCount(12, $mixes);
        $this->assertSame(13, $mixes->total());
        $this->assertStringContainsString('name=mezcla', $mixes->nextPageUrl());
        $this->assertStringContainsString('sort=name_asc', $mixes->nextPageUrl());
        $this->assertSame(['Mezcla 13'], $this->publicNames($query + ['page' => 2]));
    }

    // Spec 007, RF6: ningún valor raro da error; un id de categoría no válido da 0 resultados.
    public function test_odd_values_in_public_mixes_never_give_a_server_error(): void
    {
        $this->foreignMix('Pública');

        $this->assertSame(['Pública'], $this->publicNames(['sort' => ['x']]));
        $this->assertSame(['Pública'], $this->publicNames(['sort' => 'desconocido']));
        $this->assertSame([], $this->publicNames(['category_id' => 'abc']));
        $this->assertSame([], $this->publicNames(['category_id' => ['1']]));
        $this->assertSame([], $this->publicNames(['name' => ['x']]));
        $this->assertSame([], $this->publicNames(['flavor' => ['x']]));
        $this->assertSame(['Pública'], $this->publicNames(['name' => '   ']));
    }

    // Spec 007, RF3: el autor se carga solo con id y nombre, nunca con el email.
    public function test_the_author_is_loaded_without_the_email(): void
    {
        $this->foreignMix('Pública');

        $mix = $this->get(route('mixes.public'))->assertOk()->viewData('mixes')->first();

        $this->assertEqualsCanonicalizing(['id', 'name'], array_keys($mix->user->getAttributes()));
    }

    // Spec 007, RF1: con sesión hay dos pestañas y "Mis mezclas" es la activa en la lista propia.
    public function test_with_a_session_mixes_has_two_tabs(): void
    {
        $mine = $this->get(route('mixes.index'))->assertOk();
        $mine->assertSee('<a href="'.route('mixes.index').'" class="mixes-tab is-active" aria-current="page">Mis mezclas</a>', false);
        $mine->assertSee('<a href="'.route('mixes.public').'" class="mixes-tab">Públicas</a>', false);

        $public = $this->get(route('mixes.public'))->assertOk();
        $public->assertSee('<a href="'.route('mixes.index').'" class="mixes-tab">Mis mezclas</a>', false);
        $public->assertSee('<a href="'.route('mixes.public').'" class="mixes-tab is-active" aria-current="page">Públicas</a>', false);
    }

    // Spec 007, RF1: sin sesión no aparece la pestaña "Mis mezclas".
    public function test_a_guest_only_has_the_public_tab(): void
    {
        auth()->logout();

        $response = $this->get(route('mixes.public'))->assertOk();

        $response->assertSee('class="mixes-tab is-active" aria-current="page">Públicas</a>', false);
        $response->assertDontSee('href="'.route('mixes.index').'"', false);
        $response->assertDontSee('>Mis mezclas</a>', false);
    }

    // Spec 007, RF3: la tarjeta y la ventana muestran el autor (sin email) y no tienen Editar, Eliminar ni la etiqueta de visibilidad.
    public function test_public_mixes_show_the_author_and_no_owner_actions(): void
    {
        $author = User::factory()->create(['name' => 'Ana Autora', 'email' => 'ana.autora@example.com']);
        $mix = $this->foreignMix('Pública de Ana', [], ['user_id' => $author->id, 'notes' => 'Con hielo']);

        $response = $this->get(route('mixes.public'))->assertOk();

        $this->assertSame(2, substr_count($response->getContent(), 'por Ana Autora'));
        $response->assertSee('id="mixModal-'.$mix->id.'"', false);
        $response->assertSee('Con hielo');
        $response->assertDontSee('ana.autora@example.com');
        $response->assertDontSee('href="'.route('mixes.edit', $mix).'"', false);
        $response->assertDontSee('action="'.route('mixes.destroy', $mix).'"', false);
        $response->assertDontSee('class="mix-visibility', false);
    }

    // Spec 007, RF4: el panel muestra cada filtro con su valor y lleva el orden; "Limpiar" conserva solo el orden.
    public function test_the_public_filter_panel_shows_the_current_values_and_keeps_the_sort(): void
    {
        $citrus = \App\Models\Category::factory()->create(['name' => 'Cítrico']);

        $response = $this->get(route('mixes.public', ['name' => 'noche', 'flavor' => 'menta', 'category_id' => $citrus->id, 'sort' => 'name_desc']))->assertOk();

        $response->assertSee('name="name" id="name" class="form-control form-control-modern" value="noche"', false);
        $response->assertSee('name="flavor" id="flavor" class="form-control form-control-modern" value="menta"', false);
        $response->assertSee('<option value="'.$citrus->id.'" selected>', false);
        $response->assertSee('<input type="hidden" name="sort" value="name_desc">', false);
        $response->assertSee('href="'.route('mixes.public', ['sort' => 'name_desc']).'"', false);
    }

    // Spec 007, RF5: "Ordenar por" lista los tres órdenes y lleva los filtros activos.
    public function test_the_public_sort_selector_carries_the_active_filters(): void
    {
        $response = $this->get(route('mixes.public', ['name' => 'noche', 'flavor' => '', 'sort' => 'name_asc']))->assertOk();

        $content = $response->getContent();
        $this->assertSame(1, preg_match('#<form method="GET" action="'.preg_quote(route('mixes.public'), '#').'" class="sort-form">(.*?)</form>#s', $content, $match));

        $this->assertStringContainsString('<input type="hidden" name="name" value="noche">', $match[1]);
        $this->assertStringNotContainsString('name="flavor"', $match[1]);
        $this->assertStringContainsString('<option value="recent">Más recientes</option>', $match[1]);
        $this->assertStringContainsString('<option value="name_asc" selected>Nombre (A-Z)</option>', $match[1]);
        $this->assertStringContainsString('<option value="name_desc">Nombre (Z-A)</option>', $match[1]);
    }

    // Spec 007, RF4 y RF5: contador y paginación en la vista.
    public function test_the_public_list_shows_the_count_and_the_pagination(): void
    {
        foreach (range(1, 13) as $number) {
            $this->foreignMix(sprintf('Mezcla %02d', $number));
        }

        $response = $this->get(route('mixes.public', ['sort' => 'name_asc']))->assertOk();

        $response->assertSee('13 mezclas');
        $response->assertSee('href="'.e(route('mixes.public', ['sort' => 'name_asc', 'page' => 2])).'"', false);
    }

    // Spec 007, RF7: estados vacíos.
    public function test_the_public_list_has_its_empty_states(): void
    {
        $this->get(route('mixes.public'))
            ->assertOk()
            ->assertSee('Todavía no hay mezclas públicas')
            ->assertDontSee('No hay mezclas que coincidan');

        $this->foreignMix('Pública');

        $this->get(route('mixes.public', ['name' => 'nada', 'sort' => 'name_asc']))
            ->assertOk()
            ->assertSee('No hay mezclas que coincidan')
            ->assertSee('href="'.route('mixes.public', ['sort' => 'name_asc']).'"', false)
            ->assertDontSee('Todavía no hay mezclas públicas');
    }

    // Spec 008, RF2: la copia es privada, propia, con el mismo nombre, notas, sabores y porcentajes, y apunta a la original.
    public function test_saving_a_public_mix_creates_a_private_copy(): void
    {
        $original = $this->foreignMix('Noche de verano', [$this->first->id => 60, $this->notFavorite->id => 40], ['notes' => 'Con hielo']);

        $this->post(route('mixes.save', $original))->assertRedirect();

        $copy = Mix::where('user_id', $this->user->id)->sole();

        $this->assertSame('Noche de verano', $copy->name);
        $this->assertSame('Con hielo', $copy->notes);
        $this->assertFalse((bool) $copy->is_public);
        $this->assertSame($original->id, (int) $copy->source_mix_id);
        $this->assertEquals([$this->first->id => 60, $this->notFavorite->id => 40], $this->savedRatios($copy));
        $this->assertEquals([$this->first->id => 60, $this->notFavorite->id => 40], $this->savedRatios($original));
    }

    // Spec 008, RF2: los sabores que faltaban pasan a favoritos, sin duplicar los que ya estaban.
    public function test_saving_adds_the_missing_flavors_to_favorites(): void
    {
        $original = $this->foreignMix('Noche', [$this->first->id => 60, $this->notFavorite->id => 40]);
        $authorFavorites = Favorite::where('user_id', $original->user_id)->count();

        $this->post(route('mixes.save', $original));

        $this->assertEqualsCanonicalizing(
            [$this->first->id, $this->second->id, $this->notFavorite->id],
            Favorite::where('user_id', $this->user->id)->pluck('flavor_id')->all()
        );
        $this->assertSame(1, Favorite::where('user_id', $this->user->id)->where('flavor_id', $this->first->id)->count());
        $this->assertSame($authorFavorites, Favorite::where('user_id', $original->user_id)->count());
    }

    // Spec 008, RF3: guardar dos veces no crea otra copia; tras borrarla, se puede volver a guardar.
    public function test_a_mix_can_only_be_saved_once_until_the_copy_is_deleted(): void
    {
        $original = $this->foreignMix('Noche');

        $this->post(route('mixes.save', $original));
        $this->post(route('mixes.save', $original))
            ->assertRedirect()
            ->assertSessionHas('success', 'Ya está en Mis mezclas.');

        $this->assertSame(1, Mix::where('user_id', $this->user->id)->count());

        $copy = Mix::where('user_id', $this->user->id)->sole();
        $this->delete(route('mixes.destroy', $copy));
        $this->post(route('mixes.save', $original));

        $this->assertSame(1, Mix::where('user_id', $this->user->id)->count());
        $this->assertSame(2, Mix::count());
    }

    // Spec 008, RF2: solo se guarda lo que sale en "Públicas"; si no, 404 sin cambios.
    public function test_only_a_mix_listed_as_public_can_be_saved(): void
    {
        $hidden = Flavor::factory()->notPublic()->create();
        $private = $this->foreignMix('Privada', [], ['is_public' => false]);
        $withHidden = $this->foreignMix('Con oculto', [$this->first->id => 50, $hidden->id => 50]);
        $own = $this->savedMix([], ['name' => 'Mía']);
        $before = [Mix::count(), MixItem::count(), Favorite::count()];

        foreach ([$private, $withHidden, $own] as $mix) {
            $this->post(route('mixes.save', $mix))->assertNotFound();
        }
        $this->post('/mixes/999999/save')->assertNotFound();

        $this->assertSame($before, [Mix::count(), MixItem::count(), Favorite::count()]);
    }

    // Spec 008, RF4: con fetch responde JSON.
    public function test_saving_with_fetch_answers_json(): void
    {
        $original = $this->foreignMix('Noche');

        $this->postJson(route('mixes.save', $original))
            ->assertOk()
            ->assertExactJson(['saved' => true, 'message' => 'Guardada en Mis mezclas.']);

        $this->postJson(route('mixes.save', $original))
            ->assertOk()
            ->assertExactJson(['saved' => true, 'message' => 'Ya está en Mis mezclas.']);
    }

    // Spec 008, RF5: un visitante va a login y no se guarda nada.
    public function test_a_guest_cannot_save_a_mix(): void
    {
        $original = $this->foreignMix('Noche');
        auth()->logout();

        $this->post(route('mixes.save', $original))->assertRedirect(route('login'));

        $this->assertSame(1, Mix::count());
    }

    // Spec 008, RF1: si se borra la original, la copia se queda sin origen.
    public function test_deleting_the_original_keeps_the_copy_without_source(): void
    {
        $original = $this->foreignMix('Noche');
        $this->post(route('mixes.save', $original));

        $original->items()->delete();
        $original->delete();

        $copy = Mix::where('user_id', $this->user->id)->sole();
        $this->assertNull($copy->source_mix_id);
        $this->assertSame(1, $copy->items()->count());
    }

    // Spec 008, RF5: con sesión, "Guardar" en la tarjeta y en la ventana.
    public function test_public_mixes_have_a_save_button_in_the_card_and_the_window(): void
    {
        $mix = $this->foreignMix('Noche');

        $content = $this->get(route('mixes.public'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($content, 'action="'.route('mixes.save', $mix).'"'));
        $this->assertSame(2, substr_count($content, 'data-mix-save="'.$mix->id.'"'));
        // Icono de marcador vacío, sin texto (RF5).
        $this->assertSame(2, substr_count($content, '<button type="submit" class="mix-save" title="Guardar en Mis mezclas" aria-label="Guardar Noche en Mis mezclas"><svg class="mix-save-icon" aria-hidden="true"><use href="#mix-icon-bookmark"></use></svg></button>'));
        $this->assertStringNotContainsString('class="mix-save is-saved"', $content);
    }

    // Spec 008, RF5: si ya está guardada, "Guardada" desactivado en los dos sitios.
    public function test_an_already_saved_mix_shows_saved_disabled(): void
    {
        $mix = $this->foreignMix('Noche');
        $other = $this->foreignMix('Otra');
        $this->post(route('mixes.save', $mix));

        $content = $this->get(route('mixes.public'))->assertOk()->getContent();

        // Marcador relleno y desactivado (RF5).
        $this->assertSame(2, substr_count($content, '<button type="button" class="mix-save is-saved" title="Guardada en Mis mezclas" aria-label="Noche ya está en Mis mezclas" disabled><svg class="mix-save-icon" aria-hidden="true"><use href="#mix-icon-bookmark"></use></svg></button>'));
        $this->assertStringContainsString('<symbol id="mix-icon-bookmark"', $content);
        $this->assertSame(2, substr_count($content, 'action="'.route('mixes.save', $other).'"'));
        $this->assertStringNotContainsString('action="'.route('mixes.save', $mix).'"', $content);
    }

    // Spec 008, RF5: sin sesión, "Guardar" es un enlace a login.
    public function test_for_a_guest_save_links_to_login(): void
    {
        $mix = $this->foreignMix('Noche');
        auth()->logout();

        $content = $this->get(route('mixes.public'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($content, '<a href="'.route('login').'" class="mix-save" title="Inicia sesión para guardar" aria-label="Inicia sesión para guardar Noche"><svg class="mix-save-icon" aria-hidden="true"><use href="#mix-icon-bookmark"></use></svg></a>'));
        $this->assertStringNotContainsString(route('mixes.save', $mix), $content);
    }

    // Spec 008, RF6: en "Mis mezclas", la copia dice de quién era (sin email) y no hay botón de guardar.
    public function test_my_copy_shows_who_the_original_was_from(): void
    {
        $author = User::factory()->create(['name' => 'Ana Autora', 'email' => 'ana.autora@example.com']);
        $original = $this->foreignMix('Noche', [], ['user_id' => $author->id]);
        $this->post(route('mixes.save', $original));
        $this->savedMix([], ['name' => 'Propia']);

        $response = $this->get(route('mixes.index'))->assertOk();

        $this->assertSame(2, substr_count($response->getContent(), 'Guardada de Ana Autora'));
        $response->assertDontSee('ana.autora@example.com');
        $response->assertDontSee('class="mix-save', false);
    }

    // Spec 008, RF6: si la original ya no existe, no se muestra el crédito.
    public function test_a_copy_without_original_shows_no_credit(): void
    {
        $original = $this->foreignMix('Noche');
        $this->post(route('mixes.save', $original));
        $original->items()->delete();
        $original->delete();

        $this->get(route('mixes.index'))->assertOk()->assertDontSee('Guardada de');
    }

    // Spec 007, RF1: "Mis mezclas" sigue igual, con Editar, Eliminar y la etiqueta de visibilidad.
    public function test_my_mixes_keep_their_actions(): void
    {
        $mix = $this->savedMix();

        $response = $this->get(route('mixes.index'))->assertOk();

        $response->assertSee('href="'.route('mixes.edit', $mix).'"', false);
        $response->assertSee('action="'.route('mixes.destroy', $mix).'"', false);
        $response->assertSee('class="mix-visibility', false);
        $response->assertDontSee('class="sort-form"', false);
    }
}
