<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Favorite;
use App\Models\Flavor;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller {

    public function index() {
        $favorites = Favorite::with('flavor.brand', 'flavor.category')
                ->where('user_id', Auth::id())
                ->get();

        return view('favorites.index', compact('favorites'));
    }

    public function store(Flavor $flavor) {
        $existing = Favorite::where('user_id', Auth::id())
                ->where('flavor_id', $flavor->id);

        if ($existing->exists()) {
            $existing->delete();

            return back()->with('success', 'Sabor eliminado de favoritos.');
        }

        Favorite::create([
            'user_id' => Auth::id(),
            'flavor_id' => $flavor->id,
        ]);

        return back()->with('success', 'Sabor añadido a favoritos.');
    }
}
