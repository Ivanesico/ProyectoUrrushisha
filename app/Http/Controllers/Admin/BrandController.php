<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Models\Brand;

class BrandController extends Controller {

    public function index() {
        $brands = Brand::orderBy('name')->get();

        return view('admin.brands.index', compact('brands'));
    }

    public function create() {
        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request) {
        Brand::create([
            'name' => $request->name,
        ]);

        return redirect()
                        ->route('admin.brands.index')
                        ->with('success', 'Marca creada correctamente.');
    }

    public function edit(Brand $brand) {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(StoreBrandRequest $request, Brand $brand) {
        $brand->update([
            'name' => $request->name,
        ]);

        return redirect()
                        ->route('admin.brands.index')
                        ->with('success', 'Marca creada correctamente.');
    }

    public function destroy(Brand $brand) {

        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Marca eliminada con éxito.');
    }
}
