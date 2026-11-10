<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlavorRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Flavor;
use Illuminate\Http\Request;

class FlavorController extends Controller {

    // Filtros del listado del admin: los de la portada sin ingrediente, más visibilidad y datos incompletos.
    private const FILTERS = ['name', 'brand_id', 'category_id', 'tobacco_type', 'visibility', 'missing'];

    // Spec 005, RF5: estado de la lista que se conserva al editar, cancelar, guardar y eliminar.
    private const LIST_PARAMS = [...self::FILTERS, 'sort', 'page'];

    /**
     * Lista blanca de los parámetros de vuelta a la lista: solo las claves conocidas y con texto.
     * Así nunca se redirige a una URL libre ni pasan arrays.
     *
     * @return array<string, string>
     */
    private static function listParams(mixed $source): array {
        $params = [];

        if (!is_array($source)) {
            return $params;
        }

        foreach (self::LIST_PARAMS as $key) {
            $value = $source[$key] ?? null;

            if (is_string($value) && $value !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    // Guardar y eliminar reciben el estado de la lista en los campos ocultos list[...] (no chocan con los del sabor).
    private static function backToList(Request $request) {
        return redirect()->route('admin.flavors.index', self::listParams($request->input('list')));
    }

    public function index(Request $request) {
        // Lista blanca: un valor desconocido o un array usa el orden por defecto y no llega a la consulta.
        $sort = $request->input('sort');
        if (!is_string($sort) || !array_key_exists($sort, Flavor::ADMIN_SORTS)) {
            $sort = 'recent';
        }

        $flavors = Flavor::with(['brand', 'category'])
                ->filteredBy($request->only(self::FILTERS))
                ->sortedBy($sort)
                ->paginate(50)
                ->withQueryString();

        // Tras borrar el último sabor de una página, se vuelve a la última que exista.
        // reflash: los avisos de la sesión (éxito o error) se mostrarían en esta petición y se perderían.
        if ($flavors->total() > 0 && $flavors->currentPage() > $flavors->lastPage()) {
            $request->session()->reflash();

            return redirect($flavors->url($flavors->lastPage()));
        }

        $filters = [];

        foreach (self::FILTERS as $filter) {
            $value = $request->input($filter);
            $filters[$filter] = is_string($value) ? $value : '';
        }

        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        $listParams = self::listParams($request->query());

        return view('admin.flavors.index', compact('flavors', 'sort', 'filters', 'brands', 'categories', 'listParams') + ['sorts' => Flavor::ADMIN_SORTS]);
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

    // La ruta existe por Route::resource, pero admin no tiene página de detalle
    public function show(Flavor $flavor) {
        abort(404);
    }

    public function edit(Request $request, Flavor $flavor) {
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        // Tras un error de validación se vuelve a esta misma URL, así que los parámetros se conservan.
        $listParams = self::listParams($request->query());

        return view('admin.flavors.edit', compact('flavor', 'brands', 'categories', 'listParams'));
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

        return self::backToList($request)->with('success', 'Sabor actualizado correctamente.');
    }

    public function destroy(Request $request, Flavor $flavor) {
        if ($flavor->mixItems()->exists()) {
            return self::backToList($request)->with('error', 'No se puede eliminar el sabor porque se usa en alguna mezcla.');
        }

        $flavor->delete();

        // Si la página se queda vacía, index redirige a la última que exista.
        return self::backToList($request)->with('success', 'Sabor eliminado con éxito.');
    }
}
