<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Test users for checking the app in the browser.
 * Only runs in the local environment:
 * php artisan db:seed --class=LocalTestUsersSeeder
 */
class LocalTestUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command->error('Este seeder solo se puede ejecutar en local.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@urrushisha.test'],
            ['name' => 'Admin de prueba', 'password' => 'password', 'is_admin' => true]
        );

        User::updateOrCreate(
            ['email' => 'usuario@urrushisha.test'],
            ['name' => 'Usuario de prueba', 'password' => 'password', 'is_admin' => false]
        );
    }
}
