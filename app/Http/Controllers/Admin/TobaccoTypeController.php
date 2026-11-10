<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTobaccoTypeRequest;
use App\Models\TobaccoType;

/**
 * Spec 012: los tipos de tabaco se gestionan desde el admin, como las categorías.
 */
class TobaccoTypeController extends Controller {

    public function index() {
        $tobaccoTypes = TobaccoType::withCount('flavors')->orderBy('name')->get();

        return view('admin.tobacco-types.index', compact('tobaccoTypes'));
    }

    public function create() {
        return view('admin.tobacco-types.create');
    }

    public function store(StoreTobaccoTypeRequest $request) {
        TobaccoType::create(['name' => $request->validated('name')]);

        return redirect()
                        ->route('admin.tobacco-types.index')
                        ->with('success', 'Tipo de tabaco creado correctamente.');
    }

    // La ruta existe por Route::resource, pero admin no tiene página de detalle
    public function show(TobaccoType $tobaccoType) {
        abort(404);
    }

    public function edit(TobaccoType $tobaccoType) {
        return view('admin.tobacco-types.edit', compact('tobaccoType'));
    }

    public function update(StoreTobaccoTypeRequest $request, TobaccoType $tobaccoType) {
        $tobaccoType->update(['name' => $request->validated('name')]);

        return redirect()
                        ->route('admin.tobacco-types.index')
                        ->with('success', 'Tipo de tabaco actualizado correctamente.');
    }

    // Un tipo que usan sabores no se borra (la clave foránea tampoco lo permitiría).
    public function destroy(TobaccoType $tobaccoType) {
        $flavors = $tobaccoType->flavors()->count();

        if ($flavors > 0) {
            $message = $flavors === 1
                    ? 'No se puede eliminar el tipo porque lo usa 1 sabor.'
                    : "No se puede eliminar el tipo porque lo usan {$flavors} sabores.";

            return redirect()->route('admin.tobacco-types.index')->with('error', $message);
        }

        $tobaccoType->delete();

        return redirect()->route('admin.tobacco-types.index')->with('success', 'Tipo de tabaco eliminado con éxito.');
    }
}
