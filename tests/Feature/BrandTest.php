<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Flavor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.locale' => 'en']);
        app()->setLocale('en');
    }

    // RF-13
    public function test_it_lists_all_the_brands_ordered_by_name(): void
    {
        foreach (['Tangiers', 'Al Fakher', 'Mazaya'] as $name) {
            Brand::factory()->create(['name' => $name]);
        }

        $response = $this->actingAs(User::factory()->create())->get(route('brands.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Al Fakher', 'Mazaya', 'Tangiers']);
    }

    public function test_each_brand_links_to_its_detail(): void
    {
        $brands = Brand::factory()->count(2)->create();

        $response = $this->actingAs(User::factory()->create())->get(route('brands.index'));

        foreach ($brands as $brand) {
            $response->assertSee('href="'.route('brands.show', $brand).'"', false);
        }
    }

    public function test_the_list_shows_a_message_when_there_are_no_brands(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('brands.index'));

        $response->assertOk();
        $response->assertSee('No hay marcas disponibles');
    }

    public function test_the_list_counts_only_public_flavors(): void
    {
        $several = Brand::factory()->create();
        Flavor::factory()->count(3)->create(['brand_id' => $several->id]);
        Flavor::factory()->notPublic()->create(['brand_id' => $several->id]);

        $single = Brand::factory()->create();
        Flavor::factory()->create(['brand_id' => $single->id]);

        Brand::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get(route('brands.index'));

        $response->assertSee('3 sabores');
        $response->assertSee('1 sabor');
        $response->assertSee('Sin sabores todavía');
        $response->assertDontSee('4 sabores');
    }

    public function test_the_list_previews_up_to_three_public_images_per_brand(): void
    {
        $brand = Brand::factory()->create();

        foreach (['a', 'b', 'c', 'd'] as $name) {
            Flavor::factory()->create(['brand_id' => $brand->id, 'name' => $name, 'image_url' => "flavors/{$name}.webp"]);
        }

        Flavor::factory()->notPublic()->create(['brand_id' => $brand->id, 'name' => '0', 'image_url' => 'flavors/oculto.webp']);

        $response = $this->actingAs(User::factory()->create())->get(route('brands.index'));

        foreach (['a', 'b', 'c'] as $name) {
            $response->assertSee("storage/flavors/{$name}.webp", false);
        }

        $response->assertDontSee('storage/flavors/d.webp', false);
        $response->assertDontSee('flavors/oculto.webp', false);
    }

    // Spec 006, RF1: Marcas se ve sin sesión.
    public function test_a_guest_sees_the_list(): void
    {
        Brand::factory()->create(['name' => 'Marca visible']);

        $this->get(route('brands.index'))->assertOk()->assertSee('Marca visible');
        $this->assertGuest();
    }

    // RF-14
    public function test_the_detail_shows_the_name_and_only_its_public_flavors(): void
    {
        $brand = Brand::factory()->create(['name' => 'Al Fakher']);
        Flavor::factory()->create(['name' => 'Sabor público', 'brand_id' => $brand->id]);
        Flavor::factory()->notPublic()->create(['name' => 'Sabor oculto', 'brand_id' => $brand->id]);
        Flavor::factory()->create(['name' => 'Sabor de otra marca']);

        $response = $this->actingAs(User::factory()->create())->get(route('brands.show', $brand));

        $response->assertOk();
        $response->assertSee('Al Fakher');
        $response->assertSee('Sabor público');
        $response->assertDontSee('Sabor oculto');
        $response->assertDontSee('Sabor de otra marca');
    }

    public function test_the_detail_shows_each_flavor_data_ordered_by_name(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create(['name' => 'Frutal']);
        Flavor::factory()->create(['brand_id' => $brand->id, 'name' => 'Zarzamora']);
        Flavor::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Arándano',
            'tobacco_type' => 'sin_nicotina',
            'image_url' => 'flavors/arandano.webp',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('brands.show', $brand));

        $response->assertSeeInOrder(['Arándano', 'Zarzamora']);
        $response->assertSee('Frutal');
        $response->assertSee('Sin nicotina');
        $response->assertSee('storage/flavors/arandano.webp', false);
        $response->assertSee('href="'.route('brands.index').'"', false);
    }

    public function test_the_detail_shows_a_message_when_the_brand_has_no_public_flavors(): void
    {
        $brand = Brand::factory()->create();
        Flavor::factory()->notPublic()->create(['brand_id' => $brand->id]);

        $response = $this->actingAs(User::factory()->create())->get(route('brands.show', $brand));

        $response->assertOk();
        $response->assertSee('Esta marca no tiene sabores disponibles');
    }

    public function test_a_flavor_without_category_and_type_does_not_break_the_detail(): void
    {
        $brand = Brand::factory()->create();
        Flavor::factory()->create([
            'name' => 'Sabor incompleto',
            'brand_id' => $brand->id,
            'category_id' => null,
            'tobacco_type' => null,
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('brands.show', $brand));

        $response->assertOk();
        $response->assertSee('Sabor incompleto');
    }

    public function test_a_nonexistent_brand_responds_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/brands/999999')
            ->assertNotFound()
            ->assertSee('Página no encontrada');
    }

    // Spec 006, RF1: el detalle de una marca se ve sin sesión, solo con sus sabores públicos.
    public function test_a_guest_sees_the_detail(): void
    {
        $brand = Brand::factory()->create(['name' => 'Marca visible']);
        Flavor::factory()->for($brand)->create(['name' => 'Sabor visible']);
        Flavor::factory()->for($brand)->notPublic()->create(['name' => 'Sabor oculto']);

        $this->get(route('brands.show', $brand))
            ->assertOk()
            ->assertSee('Sabor visible')
            ->assertDontSee('Sabor oculto');
        $this->assertGuest();
    }
}
