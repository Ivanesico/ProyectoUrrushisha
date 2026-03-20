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
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\FlavorController as AdminFlavorController;

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

// TODO lo demás requiere iniciar sesión
Route::middleware('auth')->group(function () {

    // Al entrar, ver sabores directamente
    Route::get('/', [FlavorController::class, 'index'])->name('home');

    Route::get('/mixes/create', [MixController::class, 'create'])->name('mixes.create');
    Route::post('/mixes', [MixController::class, 'store'])->name('mixes.store');
    Route::get('/mixes', [MixController::class, 'index'])->name('mixes.index');

    Route::get('/mixes/{mix}/edit', [MixController::class, 'edit'])->name('mixes.edit');
    Route::put('/mixes/{mix}', [MixController::class, 'update'])->name('mixes.update');
    Route::delete('/mixes/{mix}', [MixController::class, 'destroy'])->name('mixes.destroy');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{flavor}', [FavoriteController::class, 'store'])->name('favorites.store');

    // Brands (vista normal)
    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
    Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');

    // Admin
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::resource('brands', AdminBrandController::class);
        Route::resource('categories', AdminCategoryController::class);
        Route::resource('flavors', AdminFlavorController::class);
    });
});
