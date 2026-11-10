<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMixRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Mix;
use App\Models\Flavor;
use App\Models\MixItem;
use App\Models\Category;
use Illuminate\Http\Request;

class MixController extends Controller {

    public function index() {
        $mixes = Mix::with(['items.flavor.brand'])
                ->where('user_id', auth()->id())
                ->latest()
                ->get();

        return view('mixes.index', compact('mixes') + ['tab' => 'mine']);
    }

    // Órdenes de la lista pública; el primero es el de por defecto (spec 007).
    private const PUBLIC_SORTS = [
        'recent' => 'Más recientes',
        'name_asc' => 'Nombre (A-Z)',
        'name_desc' => 'Nombre (Z-A)',
    ];

    private const PUBLIC_FILTERS = ['name', 'flavor', 'category_id'];

    /**
     * Spec 007: mezclas públicas de otros usuarios, también sin sesión.
     */
    public function publicIndex(Request $request) {
        // Lista blanca: un valor desconocido o un array usa el orden por defecto y no llega a la consulta.
        $sort = $request->input('sort');
        if (!is_string($sort) || !array_key_exists($sort, self::PUBLIC_SORTS)) {
            $sort = 'recent';
        }

        // Autor solo con id y nombre: el email nunca se carga.
        $mixes = Mix::with(['items.flavor.brand', 'user:id,name'])
                ->publicListing($request->only(self::PUBLIC_FILTERS), auth()->id())
                ->sortedForListing($sort)
                ->paginate(12)
                ->withQueryString();

        $filters = [];

        foreach (self::PUBLIC_FILTERS as $filter) {
            $value = $request->input($filter);
            $filters[$filter] = is_string($value) ? $value : '';
        }

        $categories = Category::orderBy('name')->get();

        return view('mixes.index', compact('mixes', 'filters', 'sort', 'categories') + [
            'tab' => 'public',
            'sorts' => self::PUBLIC_SORTS,
        ]);
    }

    public function create() {
        $favoriteFlavorIds = \App\Models\Favorite::where('user_id', auth()->id())
                ->pluck('flavor_id');

        $flavors = \App\Models\Flavor::whereIn('id', $favoriteFlavorIds)
                ->with('brand')
                ->orderBy('name')
                ->get();

        $rows = $this->rejectedRows() ?? $this->emptyRows();

        return view('mixes.create', compact('flavors', 'rows'));
    }

    public function store(StoreMixRequest $request) {
        DB::transaction(function () use ($request) {
            $mix = Mix::create([
                'user_id' => auth()->id(),
                'name' => $request->validated('name'),
                'notes' => $request->validated('notes'),
                'is_public' => $request->boolean('is_public'),
            ]);

            $this->saveItems($mix, $request);
        });

        return redirect()->route('mixes.index')->with('success', 'Mezcla creada correctamente.');
    }

    public function edit(Mix $mix) {
        if ($mix->user_id !== auth()->id()) {
            abort(403);
        }
        $mix->load('items');
        // Los favoritos más los sabores ya presentes, aunque hayan dejado de ser favoritos
        $flavors = Flavor::whereIn('id', function ($query) {
                    $query->select('flavor_id')
                            ->from('favorites')
                            ->where('user_id', auth()->id());
                })
                ->orWhereIn('id', $mix->items->pluck('flavor_id'))
                ->with('brand')
                ->orderBy('name')
                ->get();

        $rows = $this->rejectedRows() ?? $mix->items
                ->map(fn ($item) => ['flavor_id' => $item->flavor_id, 'ratio' => $item->ratio])
                ->all();

        return view('mixes.edit', compact('mix', 'flavors', 'rows'));
    }

    public function update(StoreMixRequest $request, Mix $mix) {
        DB::transaction(function () use ($request, $mix) {
            $mix->update([
                'name' => $request->validated('name'),
                'notes' => $request->validated('notes'),
                'is_public' => $request->boolean('is_public'),
            ]);

            MixItem::where('mix_id', $mix->id)->delete();

            $this->saveItems($mix, $request);
        });

        return redirect()->route('mixes.index')->with('success', 'Mezcla actualizada correctamente.');
    }

    public function destroy(Mix $mix) {
        if ($mix->user_id !== auth()->id()) {
            abort(403);
        }

        MixItem::where('mix_id', $mix->id)->delete();
        $mix->delete();

        return redirect()->route('mixes.index')->with('success', 'Mezcla eliminada correctamente.');
    }

    private function saveItems(Mix $mix, StoreMixRequest $request): void {
        $ratios = $request->validated('ratios');

        foreach ($request->validated('flavors') as $index => $flavorId) {
            MixItem::create([
                'mix_id' => $mix->id,
                'flavor_id' => $flavorId,
                'ratio' => (int) $ratios[$index],
            ]);
        }
    }

    /**
     * Las filas que escribió el usuario si se rechazó el formulario; null si no hay rechazo.
     * Lo que no sea un valor simple se pinta vacío, porque Blade no puede mostrar un array.
     */
    private function rejectedRows(): ?array {
        if (!session()->has('errors')) {
            return null;
        }

        $flavors = is_array(old('flavors')) ? old('flavors') : [];
        $ratios = is_array(old('ratios')) ? old('ratios') : [];
        $rows = [];

        foreach (array_keys($flavors + $ratios) as $key) {
            $flavorId = $flavors[$key] ?? '';
            $ratio = $ratios[$key] ?? '';

            $rows[] = [
                'flavor_id' => is_scalar($flavorId) ? $flavorId : '',
                'ratio' => is_scalar($ratio) ? $ratio : '',
            ];
        }

        return $rows ?: $this->emptyRows();
    }

    private function emptyRows(): array {
        return [['flavor_id' => '', 'ratio' => '']];
    }
}
