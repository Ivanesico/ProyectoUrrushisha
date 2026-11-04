<?php

namespace App\Http\Requests;

use App\Models\Flavor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlavorRequest extends FormRequest {

    public function authorize(): bool {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array {
        return [
            // bail: exists no debe consultar un valor que no sea un id válido, porque fallaría en Postgres
            'brand_id' => 'bail|required|integer|min:1|exists:brands,id',
            'category_id' => 'bail|required|integer|min:1|exists:categories,id',
            'name' => 'required|string|max:180',
            'description' => 'nullable|string',
            'tobacco_type' => ['nullable', Rule::in(Flavor::TOBACCO_TYPES)],
            'ingredients_text' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_public' => 'nullable|boolean',
        ];
    }

    public function messages(): array {
        return [
            'brand_id.required' => 'La marca es obligatoria.',
            'brand_id.integer' => 'La marca seleccionada no es válida.',
            'brand_id.min' => 'La marca seleccionada no es válida.',
            'brand_id.exists' => 'La marca seleccionada no existe.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.integer' => 'La categoría seleccionada no es válida.',
            'category_id.min' => 'La categoría seleccionada no es válida.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'name.required' => 'El nombre del sabor es obligatorio.',
            'name.string' => 'El nombre del sabor debe ser un texto.',
            'name.max' => 'El nombre del sabor no puede superar 180 caracteres.',
            'description.string' => 'La descripción debe ser un texto.',
            'tobacco_type.in' => 'El tipo de tabaco debe ser uno de estos valores: ' . implode(', ', Flavor::TOBACCO_TYPES) . '.',
            'ingredients_text.string' => 'Los ingredientes deben ser un texto.',
            'ingredients_text.max' => 'Los ingredientes no pueden superar 500 caracteres.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser jpg, jpeg, png o webp.',
            'image.max' => 'La imagen no puede superar 2048 KB.',
            'image.uploaded' => 'No se ha podido subir la imagen: puede que supere el tamaño máximo permitido por el servidor.',
            'is_public.boolean' => 'El campo público debe ser sí o no.',
        ];
    }
}
