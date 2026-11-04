<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;

class CategoryController extends Controller {

    public function index() {
        $categories = Category::orderBy('name')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create() {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request) {
        Category::create([
            'name' => $request->name,
        ]);

        return redirect()
                        ->route('admin.categories.index')
                        ->with('success', 'Categoría creada correctamente.');
    }

    // La ruta existe por Route::resource, pero admin no tiene página de detalle
    public function show(Category $category) {
        abort(404);
    }

    public function edit(Category $category) {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(StoreCategoryRequest $request, Category $category) {
        $category->update([
            'name' => $request->name,
        ]);

        return redirect()
                        ->route('admin.categories.index')
                        ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Category $category) {

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Categoría eliminada con éxito.');
    }
}
