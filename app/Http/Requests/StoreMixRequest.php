<?php

namespace App\Http\Requests;

use App\Models\Favorite;
use App\Models\Mix;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMixRequest extends FormRequest {

    /**
     * Al editar, solo el dueño: así el 403 llega antes que los errores de validación.
     */
    public function authorize(): bool {
        $mix = $this->route('mix');

        return $mix === null || $mix->user_id === $this->user()->id;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'name' => 'required|string|max:180',
            'notes' => 'nullable|string',
            'flavors' => 'required|array|min:1',
            // `bail`: `exists` no debe consultar un valor que no es un id (fallaría en Postgres)
            'flavors.*' => 'bail|required|integer|min:1|distinct|exists:flavors,id',
            'ratios' => 'required|array|min:1',
            'ratios.*' => 'required|integer|min:1|max:100',
        ];
    }

    /**
     * Comprobaciones entre campos, solo si las reglas anteriores han pasado.
     */
    public function after(): array {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $flavors = $this->input('flavors');
                $ratios = $this->input('ratios');

                if (array_diff_key($flavors, $ratios) || array_diff_key($ratios, $flavors)) {
                    $validator->errors()->add('flavors', 'Cada sabor debe tener su porcentaje en la misma posición.');
                }

                if (array_sum(array_map('intval', $ratios)) !== 100) {
                    $validator->errors()->add('ratios', 'La suma de los porcentajes debe ser exactamente 100%.');
                }

                if (array_diff(array_map('intval', $flavors), $this->allowedFlavorIds())) {
                    $validator->errors()->add('flavors', 'Solo puedes añadir sabores de tus favoritos.');
                }
            },
        ];
    }

    /**
     * Los favoritos del usuario y, al editar, los sabores guardados en la mezcla.
     *
     * @return array<int, int>
     */
    private function allowedFlavorIds(): array {
        $flavorIds = Favorite::where('user_id', $this->user()->id)->pluck('flavor_id');

        $mix = $this->route('mix');
        if ($mix instanceof Mix) {
            $flavorIds = $flavorIds->merge($mix->items()->pluck('flavor_id'));
        }

        return $flavorIds->map(fn ($id) => (int) $id)->all();
    }

    public function messages(): array {
        return [
            'name.required' => 'El nombre de la mezcla es obligatorio.',
            'name.string' => 'El nombre de la mezcla debe ser un texto.',
            'name.max' => 'El nombre de la mezcla no puede superar los 180 caracteres.',
            'notes.string' => 'Las notas deben ser un texto.',
            'flavors.required' => 'Debes añadir al menos un sabor.',
            'flavors.array' => 'Los sabores deben enviarse como una lista.',
            'flavors.min' => 'Debes añadir al menos un sabor.',
            'flavors.*.required' => 'Selecciona un sabor en cada fila.',
            'flavors.*.integer' => 'El sabor seleccionado no es válido.',
            'flavors.*.min' => 'El sabor seleccionado no es válido.',
            'flavors.*.distinct' => 'No puedes repetir un sabor en la misma mezcla.',
            'flavors.*.exists' => 'El sabor seleccionado no existe.',
            'ratios.required' => 'Debes indicar los porcentajes.',
            'ratios.array' => 'Los porcentajes deben enviarse como una lista.',
            'ratios.min' => 'Debes indicar los porcentajes.',
            'ratios.*.required' => 'Indica el porcentaje de cada sabor.',
            'ratios.*.integer' => 'Cada porcentaje debe ser un número entero.',
            'ratios.*.min' => 'Cada porcentaje debe ser al menos 1.',
            'ratios.*.max' => 'Cada porcentaje no puede superar 100.',
        ];
    }
}
