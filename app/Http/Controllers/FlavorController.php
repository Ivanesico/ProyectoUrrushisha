<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Flavor;
use App\Models\Brand;
use App\Models\Category;

class FlavorController extends Controller {

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $user = auth()->user();

        $query = Flavor::with(['brand', 'category'])
                ->where('is_public', true);

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('ingredient')) {
            $query->where('ingredients_text', 'like', '%' . $request->ingredient . '%');
        }

        if ($request->filled('tobacco_type')) {
            $query->where('tobacco_type', 'like', '%' . $request->tobacco_type . '%');
        }

        $flavors = $query->orderBy('name')->paginate(12)->withQueryString();

        $favoriteIds = \App\Models\Favorite::where('user_id', $user->id)
                ->pluck('flavor_id')
                ->toArray();

        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('index', compact('flavors', 'brands', 'categories', 'favoriteIds'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {
        //
    }
}
