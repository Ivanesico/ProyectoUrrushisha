<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\FlavorController;
use App\Http\Controllers\MixController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FlavorRatingController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\TobaccoTypeController as AdminTobaccoTypeController;
use App\Http\Controllers\Admin\FlavorController as AdminFlavorController;

// Un id que no sea un entero positivo de hasta 18 dígitos responde 404 sin llegar a la base de datos
Route::pattern('mix', '[1-9][0-9]{0,17}');
Route::pattern('flavor', '[1-9][0-9]{0,17}');
Route::pattern('brand', '[1-9][0-9]{0,17}');
Route::pattern('category', '[1-9][0-9]{0,17}');
Route::pattern('tobacco_type', '[1-9][0-9]{0,17}');

// Públicas (solo para no logueados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::get('/register', [RegisterController::class, 'index'])->name('register');

    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
});

// Logout (requiere auth)
Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

// Abiertas a todos, con o sin sesión: el catálogo y las marcas (spec 006)
Route::get('/', [FlavorController::class, 'index'])->name('home');
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
// Mezclas públicas de otros usuarios (spec 007)
Route::get('/mixes/public', [MixController::class, 'publicIndex'])->name('mixes.public');

// Lo demás requiere iniciar sesión
Route::middleware('auth')->group(function () {

    Route::get('/mixes/create', [MixController::class, 'create'])->name('mixes.create');
    Route::post('/mixes', [MixController::class, 'store'])->name('mixes.store');
    Route::get('/mixes', [MixController::class, 'index'])->name('mixes.index');

    Route::get('/mixes/{mix}/edit', [MixController::class, 'edit'])->name('mixes.edit');
    Route::put('/mixes/{mix}', [MixController::class, 'update'])->name('mixes.update');
    Route::delete('/mixes/{mix}', [MixController::class, 'destroy'])->name('mixes.destroy');
    // Guardar una copia de una mezcla pública (spec 008)
    Route::post('/mixes/{mix}/save', [MixController::class, 'saveCopy'])->name('mixes.save');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{flavor}', [FavoriteController::class, 'store'])->name('favorites.store');

    // Valoración de sabores (spec 011)
    Route::put('/flavors/{flavor}/rating', [FlavorRatingController::class, 'update'])->name('ratings.update');
    Route::delete('/flavors/{flavor}/rating', [FlavorRatingController::class, 'destroy'])->name('ratings.destroy');

    // Admin
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::resource('brands', AdminBrandController::class);
        Route::resource('categories', AdminCategoryController::class);
        // Spec 012: tipos de tabaco (parámetro {tobacco_type})
        Route::resource('tobacco-types', AdminTobaccoTypeController::class);
        Route::resource('flavors', AdminFlavorController::class);
    });
});
