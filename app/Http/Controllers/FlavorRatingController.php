<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateFlavorRequest;
use App\Models\Flavor;
use App\Models\FlavorRating;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

/**
 * Spec 011: cada usuario puntúa los sabores públicos de 1 a 5 estrellas (una por sabor).
 */
class FlavorRatingController extends Controller {

    public function update(RateFlavorRequest $request, Flavor $flavor) {
        abort_unless($flavor->is_public, 404);

        $keys = ['user_id' => auth()->id(), 'flavor_id' => $flavor->id];
        $values = ['stars' => (int) $request->validated('stars')];

        try {
            FlavorRating::updateOrCreate($keys, $values);
        } catch (UniqueConstraintViolationException) {
            // Otra petición la creó a la vez: se guarda como cambio.
            FlavorRating::where($keys)->update($values);
        }

        return $this->respond($request, $flavor, $values['stars'], 'Valoración guardada.');
    }

    public function destroy(Request $request, Flavor $flavor) {
        abort_unless($flavor->is_public, 404);

        FlavorRating::where('user_id', auth()->id())->where('flavor_id', $flavor->id)->delete();

        return $this->respond($request, $flavor, null, 'Valoración quitada.');
    }

    // Con fetch, la puntuación propia, la media con un decimal y el número; sin JS, vuelve con el aviso.
    private function respond(Request $request, Flavor $flavor, ?int $stars, string $message) {
        if ($request->expectsJson()) {
            $ratings = FlavorRating::where('flavor_id', $flavor->id);

            return response()->json([
                'stars' => $stars,
                'average' => FlavorRating::roundedAverage((clone $ratings)->avg('stars')),
                'count' => (clone $ratings)->count(),
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
