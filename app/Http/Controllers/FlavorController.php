<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Flavor;
use App\Models\FlavorRating;
use App\Models\Brand;
use App\Models\Category;

class FlavorController extends Controller {

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $user = auth()->user();

        $filterKeys = ['name', 'brand_id', 'category_id', 'ingredient', 'tobacco_type', 'min_rating'];

        // prices: "desde" en la tarjeta y la lista de la ventana (spec 010), sin una consulta por sabor.
        // ratings: media y número de valoraciones de la página (spec 011).
        $query = Flavor::with(['brand', 'category', 'tobaccoType', 'prices'])
                ->withAvg('ratings', 'stars')
                ->withCount('ratings')
                ->where('is_public', true)
                ->filteredBy($request->only($filterKeys));

        $filters = [];

        foreach ($filterKeys as $filter) {
            $value = $request->input($filter);
            $filters[$filter] = is_string($value) ? $value : '';
        }

        // Un mínimo de estrellas no válido se ignora en la consulta: tampoco se arrastra a los enlaces.
        if (!in_array($filters['min_rating'], Flavor::MIN_RATINGS, true)) {
            $filters['min_rating'] = '';
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

        // Sin sesión no hay favoritos y no se consulta la tabla (spec 006).
        $favoriteIds = $user
                ? \App\Models\Favorite::where('user_id', $user->id)->pluck('flavor_id')->toArray()
                : [];

        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        // Spec 011: la puntuación propia de los sabores de la página (flavor_id => estrellas), en una consulta.
        $userRatings = $user
                ? FlavorRating::where('user_id', $user->id)
                        ->whereIn('flavor_id', $flavors->pluck('id'))
                        ->pluck('stars', 'flavor_id')
                        ->map(fn ($stars) => (int) $stars)
                        ->all()
                : [];

        return view('index', compact('flavors', 'flavorGroups', 'brands', 'categories', 'favoriteIds', 'userRatings', 'filters', 'sort') + ['sorts' => Flavor::CATALOG_SORTS]);
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
