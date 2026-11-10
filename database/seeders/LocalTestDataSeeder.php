<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Flavor;
use App\Models\Mix;
use App\Models\MixItem;
use App\Models\TobaccoType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Test data for checking the app in the browser.
 * Only runs in the local environment, deletes nothing and does not duplicate:
 * php artisan db:seed --class=LocalTestDataSeeder
 * Requires LocalTestUsersSeeder to have been run first.
 */
class LocalTestDataSeeder extends Seeder
{
    /**
     * name => [tobacco type, ingredients]
     */
    private const PUBLIC_FLAVORS = [
        'Arándano Salvaje' => ['rubio', 'Arándano y hielo'],
        'Piña Tropical' => ['rubio', 'Piña y coco'],
        'Ámbar Especiado' => ['negro', 'Canela y clavo'],
        'Cereza Intensa' => ['rojo', 'Cereza'],
        'Menta Fresca' => ['herbal', 'Menta'],
        'Uva Negra' => ['negro', 'Uva'],
        'Sandía Dulce' => ['rubio', 'Sandía'],
        'Melón Helado' => ['sin_nicotina', 'Melón y hielo'],
        'Limón Ácido' => ['herbal', 'Limón'],
        'Coco Cremoso' => ['rubio', 'Coco'],
        'Frambuesa Suave' => ['sin_nicotina', 'Frambuesa'],
        'Manzana Doble' => ['negro', 'Manzana y anís'],
        'Mango Maduro' => ['rubio', 'Mango'],
        'Fresa Silvestre' => ['rubio', 'Fresa'],
    ];

    private const HIDDEN_FLAVOR = 'Sabor Oculto';
    private const FAVORITE_FLAVORS = ['Arándano Salvaje', 'Piña Tropical', 'Menta Fresca', self::HIDDEN_FLAVOR];
    private const FLAVOR_NOT_IN_FAVORITES = 'Uva Negra';

    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command->error('Este seeder solo se puede ejecutar en local.');
            return;
        }

        $user = User::where('email', 'usuario@urrushisha.test')->first();

        if (!$user || !User::where('email', 'admin@urrushisha.test')->exists()) {
            $this->command->error('Faltan los usuarios de prueba: ejecuta antes php artisan db:seed --class=LocalTestUsersSeeder');
            return;
        }

        DB::transaction(function () use ($user) {
            $brand = Brand::updateOrCreate(['name' => 'Marca de prueba']);
            $category = Category::updateOrCreate(['name' => 'Categoría de prueba']);

            foreach (self::PUBLIC_FLAVORS as $name => [$tobaccoType, $ingredients]) {
                $this->saveFlavor($brand, $category, $name, $tobaccoType, $ingredients, true);
            }

            $this->saveFlavor($brand, $category, self::HIDDEN_FLAVOR, 'rubio', 'Vainilla', false);

            $flavorIds = Flavor::where('brand_id', $brand->id)->pluck('id', 'name');

            foreach (self::FAVORITE_FLAVORS as $name) {
                Favorite::firstOrCreate(['user_id' => $user->id, 'flavor_id' => $flavorIds[$name]]);
            }

            $mix = Mix::updateOrCreate(
                ['user_id' => $user->id, 'name' => 'Mezcla de prueba'],
                ['notes' => 'Mezcla de prueba al 50/50', 'is_public' => false]
            );

            foreach (['Arándano Salvaje', self::FLAVOR_NOT_IN_FAVORITES] as $name) {
                MixItem::updateOrCreate(
                    ['mix_id' => $mix->id, 'flavor_id' => $flavorIds[$name]],
                    ['ratio' => 50]
                );
            }
        });
    }

    private function saveFlavor(Brand $brand, Category $category, string $name, string $tobaccoType, string $ingredients, bool $isPublic): void
    {
        Flavor::updateOrCreate(
            ['brand_id' => $brand->id, 'name' => $name],
            [
                'category_id' => $category->id,
                'description' => "{$name}: sabor de prueba.",
                // Spec 012: el tipo por su nombre legible ("sin_nicotina" → "Sin nicotina").
                'tobacco_type_id' => TobaccoType::firstOrCreate(['name' => ucfirst(str_replace('_', ' ', $tobaccoType))])->id,
                'ingredients_text' => $ingredients,
                'is_public' => $isPublic,
            ]
        );
    }
}
