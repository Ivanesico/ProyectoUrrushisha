<?php

namespace App\Http\Requests;

use App\Models\Flavor;
use App\Models\TobaccoType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Spec 012: nombre de un tipo de tabaco, obligatorio, de hasta 60 caracteres y sin repetir
 * (sin distinguir mayúsculas ni acentos: "Négro" choca con "Negro").
 */
class StoreTobaccoTypeRequest extends FormRequest {

    public function authorize(): bool {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array {
        return [
            'name' => 'bail|required|string|max:60',
        ];
    }

    public function after(): array {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $current = $this->route('tobacco_type');
                $name = Flavor::normalizeSearchTerm($this->input('name'));

                // Son pocos tipos: se comparan en PHP, igual en los tres motores.
                $taken = TobaccoType::query()
                        ->when($current instanceof TobaccoType, fn ($query) => $query->whereKeyNot($current->id))
                        ->pluck('name')
                        ->contains(fn ($existing) => Flavor::normalizeSearchTerm($existing) === $name);

                if ($taken) {
                    $validator->errors()->add('name', 'Este tipo de tabaco ya existe.');
                }
            },
        ];
    }

    public function messages(): array {
        return [
            'name.required' => 'El nombre del tipo de tabaco es obligatorio.',
            'name.string' => 'El nombre del tipo de tabaco debe ser un texto.',
            'name.max' => 'El nombre del tipo de tabaco no puede superar 60 caracteres.',
        ];
    }
}
