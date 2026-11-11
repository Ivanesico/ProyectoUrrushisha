<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMixRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Mix;
use App\Models\Flavor;
use App\Models\MixItem;
use App\Models\Category;
use App\Models\Favorite;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class MixController extends Controller {

    public function index() {
        // source.user: "Guardada de <autor>" en las copias (spec 008), solo con id y nombre.
        $mixes = Mix::with(['items.flavor.brand', 'source.user:id,name'])
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

        // Mezclas de esta página que el usuario ya ha guardado (spec 008): su botón sale como "Guardada".
        $savedSourceIds = auth()->check()
                ? Mix::where('user_id', auth()->id())
                        ->whereIn('source_mix_id', $mixes->pluck('id'))
                        ->pluck('source_mix_id')
                        ->map(fn ($id) => (int) $id)
                        ->all()
                : [];

        return view('mixes.index', compact('mixes', 'filters', 'sort', 'categories', 'savedSourceIds') + [
            'tab' => 'public',
            'sorts' => self::PUBLIC_SORTS,
        ]);
    }

    /**
     * Spec 008: guarda una copia privada de una mezcla pública en "Mis mezclas" y añade a
     * favoritos los sabores que falten. Solo vale para una mezcla que el usuario ve en "Públicas".
     * Una segunda vez no crea otra copia (también lo impide el único de la base de datos).
     */
    public function saveCopy(Request $request, Mix $mix) {
        $userId = auth()->id();

        $original = Mix::with('items')
                ->publicListing([], $userId)
                ->whereKey($mix->id)
                ->firstOrFail();

        $alreadySaved = Mix::where('user_id', $userId)->where('source_mix_id', $original->id)->exists();

        if (!$alreadySaved) {
            try {
                DB::transaction(function () use ($original, $userId) {
                    $copy = new Mix([
                        'user_id' => $userId,
                        'name' => $original->name,
                        'notes' => $original->notes,
                        'is_public' => false,
                    ]);
                    $copy->source_mix_id = $original->id;
                    $copy->save();

                    // mix_items.role no se usa: solo se copian sabor y porcentaje.
                    foreach ($original->items as $item) {
                        MixItem::create([
                            'mix_id' => $copy->id,
                            'flavor_id' => $item->flavor_id,
                            'ratio' => $item->ratio,
                        ]);
                    }

                    // favorites no tiene id: se insertan solo los que faltan.
                    $flavorIds = $original->items->pluck('flavor_id')->unique();
                    $existing = Favorite::where('user_id', $userId)->whereIn('flavor_id', $flavorIds)->pluck('flavor_id');

                    foreach ($flavorIds->diff($existing) as $flavorId) {
                        Favorite::create(['user_id' => $userId, 'flavor_id' => $flavorId]);
                    }
                });
            } catch (UniqueConstraintViolationException) {
                // Otra petición la guardó a la vez: el resultado es el mismo.
                $alreadySaved = true;
            }
        }

        $message = $alreadySaved ? 'Ya está en Mis mezclas.' : 'Guardada en Mis mezclas.';

        if ($request->expectsJson()) {
            return response()->json(['saved' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function create(Request $request) {
        $flavorGroups = $this->flavorGroups();

        // Tras un error manda lo escrito; si no, el sabor del enlace "Crear mezcla" (spec 013) o una fila sin sabor.
        // Spec 014: una mezcla nueva empieza con 2 filas al 50 % (lo pone el servidor para que funcione también sin JavaScript).
        $rows = $this->rejectedRows() ?? $this->linkedFlavorRows($request) ?? [
            ['flavor_id' => '', 'ratio' => '50'],
            ['flavor_id' => '', 'ratio' => '50'],
        ];

        return view('mixes.create', compact('flavorGroups', 'rows'));
    }

    /**
     * Spec 013: primera fila con el sabor de ?flavor=, si es un sabor público (la misma regla que al guardar).
     * Un id no válido no llega a la consulta (Postgres lo rechazaría) y cualquier otro valor se ignora.
     */
    private function linkedFlavorRows(Request $request): ?array {
        $value = $request->query('flavor');
        $id = is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;

        if ($id === false || !Flavor::whereKey($id)->where('is_public', true)->exists()) {
            return null;
        }

        // Spec 014: el sabor del enlace al 50 % y una segunda fila sin sabor al 50 %.
        return [
            ['flavor_id' => $id, 'ratio' => '50'],
            ['flavor_id' => '', 'ratio' => '50'],
        ];
    }

    /**
     * Spec 009: sabores que ofrece el formulario de mezcla: todos los públicos y los ya presentes
     * en la mezcla ($presentIds, al editar). Primero los favoritos y luego el resto, cada grupo por nombre.
     * Se ordena en PHP para no depender del orden de cada motor.
     *
     * @param  \Illuminate\Support\Collection<int, int>|array<int, int>  $presentIds
     * @return array<int, array{label: string, favorite: bool, flavors: \Illuminate\Support\Collection}>
     */
    private function flavorGroups($presentIds = []): array {
        $favoriteIds = Favorite::where('user_id', auth()->id())->pluck('flavor_id')->map(fn ($id) => (int) $id)->all();

        $flavors = Flavor::with('brand')
                ->where(fn ($query) => $query->where('is_public', true)->orWhereIn('id', $presentIds))
                ->get()
                // Sin mayúsculas ni acentos, como el buscador; desempate por id.
                ->sortBy([
                    fn (Flavor $a, Flavor $b) => Flavor::normalizeSearchTerm($a->name) <=> Flavor::normalizeSearchTerm($b->name),
                    fn (Flavor $a, Flavor $b) => $a->id <=> $b->id,
                ])
                ->values();

        [$favorites, $others] = $flavors->partition(fn (Flavor $flavor) => in_array($flavor->id, $favoriteIds, true));

        $groups = [];

        if ($favorites->isNotEmpty()) {
            $groups[] = ['label' => 'Tus favoritos', 'favorite' => true, 'flavors' => $favorites->values()];
        }

        if ($others->isNotEmpty()) {
            $groups[] = ['label' => 'Resto del catálogo', 'favorite' => false, 'flavors' => $others->values()];
        }

        return $groups;
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
        // El catálogo público más los sabores ya presentes, aunque hayan dejado de ser públicos (spec 009)
        $flavorGroups = $this->flavorGroups($mix->items->pluck('flavor_id'));

        $rows = $this->rejectedRows();

        if ($rows === null) {
            $rows = $mix->items
                    ->map(fn ($item) => ['flavor_id' => $item->flavor_id, 'ratio' => $item->ratio])
                    ->all();

            // Spec 014, RF8: una mezcla antigua de 1 sabor se edita con una 2.ª fila vacía (hacen falta 2 para guardar).
            if (count($rows) === 1) {
                $rows[] = ['flavor_id' => '', 'ratio' => ''];
            }
        }

        return view('mixes.edit', compact('mix', 'flavorGroups', 'rows'));
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
