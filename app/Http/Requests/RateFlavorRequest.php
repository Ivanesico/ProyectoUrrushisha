<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Spec 011: valoración de un sabor, de 1 a 5 estrellas enteras.
 */
class RateFlavorRequest extends FormRequest {

    private const STARS_MESSAGE = 'La valoración debe ser de 1 a 5 estrellas.';

    public function authorize(): bool {
        return auth()->check();
    }

    public function rules(): array {
        return [
            'stars' => 'bail|required|integer|between:1,5',
        ];
    }

    public function messages(): array {
        return [
            'stars.required' => self::STARS_MESSAGE,
            'stars.integer' => self::STARS_MESSAGE,
            'stars.between' => self::STARS_MESSAGE,
        ];
    }
}
