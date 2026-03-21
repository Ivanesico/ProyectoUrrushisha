<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlavorRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Flavor;

class FlavorController extends Controller {

    public function index() {
        $flavors = Flavor::with(['brand', 'category'])
                ->orderBy('created_at', 'desc')
                ->get();

        return view('admin.flavors.index', compact('flavors'));
    }

    public function create() {
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.flavors.create', compact('brands', 'categories'));
    }

    public function store(StoreFlavorRequest $request) {
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('flavors', 'public');
        }

        Flavor::create([
            'brand_id' => $request->brand_id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'tobacco_type' => $request->tobacco_type,
            'ingredients_text' => $request->ingredients_text,
            'image_url' => $imagePath,
            'created_by' => auth()->id(),
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()
                        ->route('admin.flavors.index')
                        ->with('success', 'Sabor creado correctamente.');
    }

    public function edit(Flavor $flavor) {
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        return view('admin.flavors.edit', compact('flavor', 'brands', 'categories'));
    }

    public function update(StoreFlavorRequest $request, Flavor $flavor) {
        

        $imagePath = $flavor->image_url;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('flavors', 'public');
        }

        $flavor->update([
            'brand_id' => $request->brand_id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'tobacco_type' => $request->tobacco_type,
            'ingredients_text' => $request->ingredients_text,
            'image_url' => $imagePath,
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()
                        ->route('admin.flavors.index')
                        ->with('success', 'Sabor actualizado correctamente.');
    }

    public function destroy(Flavor $flavor) {

        $flavor->delete();

        return redirect()->route('admin.flavors.index')->with('success', 'Sabor eliminado con éxito.');
    }
}
