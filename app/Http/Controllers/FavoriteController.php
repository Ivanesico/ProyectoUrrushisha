<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Favorite;
use App\Models\Flavor;
use App\Models\FlavorRating;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller {

    private const SORTS = [
        'name_asc' => 'Nombre (A-Z)',
        'name_desc' => 'Nombre (Z-A)',
        'brand' => 'Marca',
        'category' => 'Categoría',
        'type' => 'Tipo de tabaco',
        'recent' => 'Añadidos recientemente',
    ];

    // Se filtra y ordena en PHP: son solo los favoritos de un usuario, el resultado es igual
    // en MySQL y en Postgres, y un valor no válido de la URL nunca llega a una consulta.
    public function index(Request $request) {
        // Spec 011: media y número de valoraciones para la ventana de detalle.
        $allFavorites = Favorite::with([
                    'flavor' => fn ($query) => $query->withAvg('ratings', 'stars')->withCount('ratings'),
                    'flavor.brand',
                    'flavor.category',
                    'flavor.tobaccoType',
                    'flavor.prices',
                ])
                ->where('user_id', Auth::id())
                ->get();

        // Spec 011: puntuación propia de los favoritos (flavor_id => estrellas), en una consulta.
        $userRatings = FlavorRating::where('user_id', Auth::id())
                ->whereIn('flavor_id', $allFavorites->pluck('flavor_id'))
                ->pluck('stars', 'flavor_id')
                ->map(fn ($stars) => (int) $stars)
                ->all();

        $sort = $request->input('sort');
        $sort = is_string($sort) && array_key_exists($sort, self::SORTS) ? $sort : 'name_asc';

        $favorites = $allFavorites->filter(fn (Favorite $favorite) => $this->matchesFilters($favorite->flavor, $request));
        $favorites = $this->sorted($favorites, $sort);

        return view('favorites.index', [
            'favoriteGroups' => $favorites->groupBy(fn (Favorite $favorite) => $this->groupLabel($favorite->flavor, $sort)),
            'matchingCount' => $favorites->count(),
            'totalCount' => $allFavorites->count(),
            'filters' => $this->filterValues($request),
            'sort' => $sort,
            'sorts' => self::SORTS,
            'brands' => $this->optionsFrom($allFavorites, 'brand'),
            'categories' => $this->optionsFrom($allFavorites, 'category'),
            // Spec 012: los tipos de la tabla que tienen los favoritos, como marcas y categorías.
            'tobaccoTypes' => $this->optionsFrom($allFavorites, 'tobaccoType'),
            'userRatings' => $userRatings,
        ]);
    }

    // Con una petición JSON (el corazón con fetch) responde sin redirigir, para no mover la página.
    public function store(Request $request, Flavor $flavor) {
        $existing = Favorite::where('user_id', Auth::id())
                ->where('flavor_id', $flavor->id);

        if ($existing->exists()) {
            $existing->delete();

            return $this->toggled($request, false, 'Sabor eliminado de favoritos.');
        }

        Favorite::create([
            'user_id' => Auth::id(),
            'flavor_id' => $flavor->id,
        ]);

        return $this->toggled($request, true, 'Sabor añadido a favoritos.');
    }

    private function toggled(Request $request, bool $favorited, string $message) {
        if ($request->expectsJson()) {
            return response()->json(['favorited' => $favorited, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    private function matchesFilters(Flavor $flavor, Request $request): bool {
        // Spec 012: el tipo de tabaco se filtra por id, como la marca y la categoría.
        foreach (['brand_id', 'category_id', 'tobacco_type_id'] as $column) {
            if ($request->filled($column)) {
                $id = filter_var($request->input($column), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

                if ($id === false || (int) $flavor->{$column} !== $id) {
                    return false;
                }
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            if (!is_string($search)) {
                return false;
            }

            $text = Flavor::normalizeSearchTerm($flavor->name . ' ' . $flavor->ingredients_text);

            return str_contains($text, Flavor::normalizeSearchTerm(trim($search)));
        }

        return true;
    }

    private function sorted(Collection $favorites, string $sort): Collection {
        if ($sort === 'recent') {
            return $favorites->sortByDesc('created_at')->values();
        }

        $sorted = $favorites->sort(fn (Favorite $a, Favorite $b) => $this->sortKey($a->flavor, $sort) <=> $this->sortKey($b->flavor, $sort));

        return ($sort === 'name_desc' ? $sorted->reverse() : $sorted)->values();
    }

    /**
     * Lo que no tiene valor va al final; dentro de cada grupo, por nombre.
     */
    private function sortKey(Flavor $flavor, string $sort): array {
        $value = $this->sortValue($flavor, $sort);

        return [
            $value === null ? 1 : 0,
            Flavor::normalizeSearchTerm($value ?? ''),
            Flavor::normalizeSearchTerm($flavor->name),
        ];
    }

    private function sortValue(Flavor $flavor, string $sort): ?string {
        return match ($sort) {
            'brand' => $flavor->brand?->name,
            'category' => $flavor->category?->name,
            'type' => $flavor->tobacco_type_label,
            default => '',
        };
    }

    /**
     * El título del grupo al ordenar por un campo; vacío si el orden no agrupa.
     */
    private function groupLabel(Flavor $flavor, string $sort): string {
        return match ($sort) {
            'brand' => $flavor->brand?->name ?? 'Sin marca',
            'category' => $flavor->category?->name ?? 'Sin categoría',
            'type' => $flavor->tobacco_type_label ?? 'Sin tipo',
            default => '',
        };
    }

    private function filterValues(Request $request): array {
        $filters = [];

        foreach (['search', 'brand_id', 'category_id', 'tobacco_type_id'] as $filter) {
            $value = $request->input($filter);
            $filters[$filter] = is_string($value) ? $value : '';
        }

        return $filters;
    }

    private function optionsFrom(Collection $favorites, string $relation): Collection {
        return $favorites
                ->map(fn (Favorite $favorite) => $favorite->flavor->{$relation})
                ->filter()
                ->unique('id')
                ->sortBy(fn ($option) => Flavor::normalizeSearchTerm($option->name))
                ->values();
    }
}
