<?php

namespace App\Http\Requests;

use App\Models\Flavor;
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
            // Spec 014, RF7: una mezcla necesita al menos 2 sabores (con menos, after() no se ejecuta)
            'flavors' => 'required|array|min:2',
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

                $flavorIds = array_map('intval', $flavors);

                if (array_diff($flavorIds, $this->allowedFlavorIds($flavorIds))) {
                    $validator->errors()->add('flavors', 'Solo puedes añadir sabores del catálogo.');
                }
            },
        ];
    }

    /**
     * Spec 009: de los sabores enviados, los públicos y, al editar, los que ya estaban en la mezcla
     * (aunque hayan dejado de ser públicos). Ya no depende de los favoritos.
     *
     * @param  array<int, int>  $flavorIds
     * @return array<int, int>
     */
    private function allowedFlavorIds(array $flavorIds): array {
        $allowed = Flavor::whereIn('id', $flavorIds)->where('is_public', true)->pluck('id');

        $mix = $this->route('mix');
        if ($mix instanceof Mix) {
            $allowed = $allowed->merge($mix->items()->pluck('flavor_id'));
        }

        return $allowed->map(fn ($id) => (int) $id)->all();
    }

    public function messages(): array {
        return [
            'name.required' => 'El nombre de la mezcla es obligatorio.',
            'name.string' => 'El nombre de la mezcla debe ser un texto.',
            'name.max' => 'El nombre de la mezcla no puede superar los 180 caracteres.',
            'notes.string' => 'Las notas deben ser un texto.',
            'flavors.required' => 'Una mezcla necesita al menos 2 sabores.',
            'flavors.array' => 'Los sabores deben enviarse como una lista.',
            'flavors.min' => 'Una mezcla necesita al menos 2 sabores.',
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
