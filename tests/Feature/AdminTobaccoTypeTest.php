<?php

namespace Tests\Feature;

use App\Models\Flavor;
use App\Models\TobaccoType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Spec 012, T2: sección "Tipos de tabaco" del admin.
 */
class AdminTobaccoTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    // RF2: lista por nombre con el número de sabores de cada tipo.
    public function test_the_list_shows_each_type_with_its_flavor_count(): void
    {
        Flavor::factory()->count(2)->create(['tobacco_type_id' => $this->tobaccoType('negro')]);
        Flavor::factory()->create(['tobacco_type_id' => $this->tobaccoType('rubio')]);

        $response = $this->get(route('admin.tobacco-types.index'))->assertOk();

        $response->assertSeeInOrder(['Base', 'Herbal', 'Negro', 'Rojo', 'Rubio', 'Sin nicotina']);
        $response->assertSee('<td>Negro</td>', false);
        $response->assertSee('<td class="text-end">2</td>', false);
        $response->assertSee('href="'.route('admin.tobacco-types.create').'"', false);
        $response->assertSee('href="'.route('admin.tobacco-types.edit', $this->tobaccoType('negro')).'"', false);
        $response->assertSee('action="'.route('admin.tobacco-types.destroy', $this->tobaccoType('herbal')).'"', false);
    }

    // RF2: la sección está enlazada en la fila de secciones del admin.
    public function test_the_admin_sections_link_to_tobacco_types(): void
    {
        $this->get(route('admin.index'))->assertSee('href="'.route('admin.tobacco-types.index').'"', false)->assertSee('Tipos de tabaco');
    }

    // RF2: crear un tipo nuevo.
    public function test_admin_creates_a_type(): void
    {
        $this->get(route('admin.tobacco-types.create'))->assertOk()->assertSee('Añadir tipo de tabaco');

        $this->post(route('admin.tobacco-types.store'), ['name' => 'Virginia'])
            ->assertRedirect(route('admin.tobacco-types.index'))
            ->assertSessionHas('success', 'Tipo de tabaco creado correctamente.');

        $this->assertDatabaseHas('tobacco_types', ['name' => 'Virginia']);
    }

    // RF2 y RF4: renombrar un tipo lo cambia en todos sus sabores.
    public function test_renaming_a_type_changes_it_in_its_flavors(): void
    {
        $id = $this->tobaccoType('sin_nicotina');
        Flavor::factory()->create(['name' => 'Menta', 'tobacco_type_id' => $id]);

        $this->get(route('admin.tobacco-types.edit', $id))->assertOk()->assertSee('value="Sin nicotina"', false);

        $this->put(route('admin.tobacco-types.update', $id), ['name' => 'Libre de nicotina'])
            ->assertRedirect(route('admin.tobacco-types.index'))
            ->assertSessionHas('success', 'Tipo de tabaco actualizado correctamente.');

        $this->get(route('home'))->assertSee('Libre de nicotina');
        $this->assertSame('Libre de nicotina', Flavor::where('name', 'Menta')->sole()->tobacco_type_label);
    }

    // RF2: guardar sin cambiar el nombre no choca con él mismo.
    public function test_saving_a_type_without_changing_the_name_works(): void
    {
        $id = $this->tobaccoType('rojo');

        $this->put(route('admin.tobacco-types.update', $id), ['name' => 'Rojo'])->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function invalidNames(): array
    {
        return [
            'vacío' => ['', 'El nombre del tipo de tabaco es obligatorio.'],
            'solo espacios' => ['   ', 'El nombre del tipo de tabaco es obligatorio.'],
            'de 61 caracteres' => [str_repeat('a', 61), 'El nombre del tipo de tabaco no puede superar 60 caracteres.'],
            'repetido' => ['Negro', 'Este tipo de tabaco ya existe.'],
            'repetido en mayúsculas' => ['NEGRO', 'Este tipo de tabaco ya existe.'],
            'repetido con acentos' => ['Négro', 'Este tipo de tabaco ya existe.'],
            'como array' => [['Negro'], 'El nombre del tipo de tabaco debe ser un texto.'],
        ];
    }

    // RF2: nombre obligatorio, de hasta 60 caracteres y sin repetir (sin mayúsculas ni acentos).
    #[DataProvider('invalidNames')]
    public function test_invalid_names_are_rejected_without_saving(mixed $name, string $message): void
    {
        $before = TobaccoType::orderBy('id')->pluck('name', 'id')->all();

        $this->from(route('admin.tobacco-types.create'))
            ->post(route('admin.tobacco-types.store'), ['name' => $name])
            ->assertRedirect(route('admin.tobacco-types.create'))
            ->assertSessionHasErrors(['name' => $message]);

        $rojo = $this->tobaccoType('rojo');
        $this->from(route('admin.tobacco-types.edit', $rojo))
            ->put(route('admin.tobacco-types.update', $rojo), ['name' => $name])
            ->assertSessionHasErrors(['name' => $message]);

        $this->assertSame($before, TobaccoType::orderBy('id')->pluck('name', 'id')->all());
    }

    // RF2: el error se ve en el formulario y conserva lo escrito.
    public function test_the_form_shows_the_error_and_keeps_the_name(): void
    {
        $this->from(route('admin.tobacco-types.create'))
            ->followingRedirects()
            ->post(route('admin.tobacco-types.store'), ['name' => 'Negro'])
            ->assertOk()
            ->assertSee('Este tipo de tabaco ya existe.')
            ->assertSee('value="Negro"', false);
    }

    // RF2: un tipo sin sabores se puede borrar.
    public function test_admin_deletes_an_unused_type(): void
    {
        $id = $this->tobaccoType('herbal');

        $this->delete(route('admin.tobacco-types.destroy', $id))
            ->assertRedirect(route('admin.tobacco-types.index'))
            ->assertSessionHas('success', 'Tipo de tabaco eliminado con éxito.');

        $this->assertDatabaseMissing('tobacco_types', ['id' => $id]);
    }

    // RF2: un tipo en uso no se borra, con el número de sabores en el mensaje.
    public function test_a_type_in_use_cannot_be_deleted(): void
    {
        $negro = $this->tobaccoType('negro');
        $rojo = $this->tobaccoType('rojo');
        Flavor::factory()->count(3)->create(['tobacco_type_id' => $negro]);
        Flavor::factory()->create(['tobacco_type_id' => $rojo]);

        $this->delete(route('admin.tobacco-types.destroy', $negro))
            ->assertRedirect(route('admin.tobacco-types.index'))
            ->assertSessionHas('error', 'No se puede eliminar el tipo porque lo usan 3 sabores.');
        $this->delete(route('admin.tobacco-types.destroy', $rojo))
            ->assertSessionHas('error', 'No se puede eliminar el tipo porque lo usa 1 sabor.');

        $this->assertDatabaseHas('tobacco_types', ['id' => $negro]);
        $this->assertDatabaseHas('tobacco_types', ['id' => $rojo]);

        $this->get(route('admin.tobacco-types.index'))->assertOk();
    }

    // RF2: el aviso de error se ve en la lista.
    public function test_the_list_shows_the_error_message(): void
    {
        $negro = $this->tobaccoType('negro');
        Flavor::factory()->create(['tobacco_type_id' => $negro]);

        $this->followingRedirects()
            ->delete(route('admin.tobacco-types.destroy', $negro))
            ->assertSee('No se puede eliminar el tipo porque lo usa 1 sabor.');
    }

    // RF2: un id inexistente o no numérico da 404.
    public function test_a_missing_type_responds_404(): void
    {
        $this->get('/admin/tobacco-types/999999/edit')->assertNotFound();
        $this->get('/admin/tobacco-types/abc/edit')->assertNotFound();
    }

    // RF2: solo admins.
    public function test_only_admins_can_manage_types(): void
    {
        $id = $this->tobaccoType('herbal');

        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get(route('admin.tobacco-types.index'))->assertForbidden();
        $this->post(route('admin.tobacco-types.store'), ['name' => 'Virginia'])->assertForbidden();
        $this->delete(route('admin.tobacco-types.destroy', $id))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.tobacco-types.index'))->assertRedirect(route('login'));

        $this->assertDatabaseMissing('tobacco_types', ['name' => 'Virginia']);
        $this->assertDatabaseHas('tobacco_types', ['id' => $id]);
    }
}
