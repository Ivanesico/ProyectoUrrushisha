<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Mix;
use App\Models\Flavor;
use App\Models\MixItem;

class MixController extends Controller {

    public function index() {
        $mixes = Mix::with(['items.flavor'])
                ->where('user_id', auth()->id())
                ->latest()
                ->get();

        return view('mixes.index', compact('mixes'));
    }

    public function create() {
        $favoriteFlavorIds = \App\Models\Favorite::where('user_id', auth()->id())
                ->pluck('flavor_id');

        $flavors = \App\Models\Flavor::whereIn('id', $favoriteFlavorIds)
                ->with('brand')
                ->orderBy('name')
                ->get();

        return view('mixes.create', compact('flavors'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'flavors' => 'required|array|min:1',
            'flavors.*' => 'required|exists:flavors,id',
            'ratios' => 'required|array|min:1',
            'ratios.*' => 'required|integer|min:1|max:100',
                ], [
            'name.required' => 'El nombre de la mezcla es obligatorio.',
            'flavors.required' => 'Debes añadir al menos un sabor.',
        ]);

        $totalRatio = array_sum(array_map('intval', $request->ratios));
        if ($totalRatio !== 100) {
            return back()
                            ->withErrors(['ratios' => 'La suma de los porcentajes debe ser exactamente 100%.'])
                            ->withInput();
        }

        $mix = Mix::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'notes' => $request->notes,
            'is_public' => $request->boolean('is_public'),
        ]);

        foreach ($request->flavors as $index => $flavorId) {
            MixItem::create([
                'mix_id' => $mix->id,
                'flavor_id' => $flavorId,
                'ratio' => $request->ratios[$index],
            ]);
        }

        return redirect()->route('mixes.index')->with('success', 'Mezcla creada correctamente.');
    }

    public function edit(Mix $mix) {
        if ($mix->user_id !== auth()->id()) {
            abort(403);
        }
        $mix->load('items');
        $flavors = Flavor::whereIn('id', function ($query) {
                    $query->select('flavor_id')
                            ->from('favorites')
                            ->where('user_id', auth()->id());
                })
                ->with('brand')
                ->orderBy('name')
                ->get();
        return view('mixes.edit', compact('mix', 'flavors'));
    }

    public function update(Request $request, Mix $mix) {
        if ($mix->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'flavors' => 'required|array|min:1',
            'flavors.*' => 'required|exists:flavors,id',
            'ratios' => 'required|array|min:1',
            'ratios.*' => 'required|integer|min:1|max:100',
        ]);

        $totalRatio = array_sum(array_map('intval', $request->ratios));
        if ($totalRatio !== 100) {
            return back()
                            ->withErrors(['ratios' => 'La suma de los porcentajes debe ser exactamente 100%.'])
                            ->withInput();
        }

        $mix->update([
            'name' => $request->name,
            'notes' => $request->notes,
            'is_public' => $request->boolean('is_public'),
        ]);

        MixItem::where('mix_id', $mix->id)->delete();

        foreach ($request->flavors as $index => $flavorId) {
            MixItem::create([
                'mix_id' => $mix->id,
                'flavor_id' => $flavorId,
                'ratio' => $request->ratios[$index],
            ]);
        }

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
}
