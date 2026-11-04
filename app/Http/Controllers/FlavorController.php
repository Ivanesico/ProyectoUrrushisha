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

        // Un valor no válido da 0 resultados y nunca llega a la consulta: Postgres lo rechazaría.
        foreach (['brand_id', 'category_id'] as $column) {
            if ($request->filled($column)) {
                $id = filter_var($request->input($column), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

                if ($id === false) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where($column, $id);
                }
            }
        }

        $textFilters = ['name' => 'name', 'ingredient' => 'ingredients_text', 'tobacco_type' => 'tobacco_type'];

        foreach ($textFilters as $filter => $column) {
            if ($request->filled($filter)) {
                $term = $request->input($filter);

                if (is_string($term)) {
                    $query->matchingText($column, $term);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        $filters = [];

        foreach (['name', 'brand_id', 'category_id', 'ingredient', 'tobacco_type'] as $filter) {
            $value = $request->input($filter);
            $filters[$filter] = is_string($value) ? $value : '';
        }

        $flavors = $query->orderBy('name')->paginate(12)->withQueryString();

        $favoriteIds = \App\Models\Favorite::where('user_id', $user->id)
                ->pluck('flavor_id')
                ->toArray();

        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('index', compact('flavors', 'brands', 'categories', 'favoriteIds', 'filters'));
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
