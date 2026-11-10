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

        $filterKeys = ['name', 'brand_id', 'category_id', 'ingredient', 'tobacco_type'];

        $query = Flavor::with(['brand', 'category'])
                ->where('is_public', true)
                ->filteredBy($request->only($filterKeys));

        $filters = [];

        foreach ($filterKeys as $filter) {
            $value = $request->input($filter);
            $filters[$filter] = is_string($value) ? $value : '';
        }

        // Lista blanca: un valor desconocido o un array usa el orden por defecto y no llega a la consulta.
        $sort = $request->input('sort');
        if (!is_string($sort) || !array_key_exists($sort, Flavor::CATALOG_SORTS)) {
            $sort = 'recent';
        }

        $flavors = $query->sortedBy($sort)->paginate(12)->withQueryString()->fragment('catalogo');

        // Al ordenar por marca o categoría, los sabores de la página se agrupan bajo su título; si no, un solo grupo sin título.
        $flavorGroups = $flavors->getCollection()->groupBy(fn (Flavor $flavor) => match ($sort) {
            'brand' => $flavor->brand?->name ?? 'Sin marca',
            'category' => $flavor->category?->name ?? 'Sin categoría',
            default => '',
        });

        $favoriteIds = \App\Models\Favorite::where('user_id', $user->id)
                ->pluck('flavor_id')
                ->toArray();

        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('index', compact('flavors', 'flavorGroups', 'brands', 'categories', 'favoriteIds', 'filters', 'sort') + ['sorts' => Flavor::CATALOG_SORTS]);
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
