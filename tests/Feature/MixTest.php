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
}
