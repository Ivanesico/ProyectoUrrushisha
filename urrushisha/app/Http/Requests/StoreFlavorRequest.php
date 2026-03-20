<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlavorRequest extends FormRequest {

    public function authorize(): bool {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array {
        return [
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tobacco_type' => 'nullable|string|max:100',
            'ingredients_text' => 'nullable|string',
            'image_url' => 'nullable|url|max:2048',
            'is_public' => 'nullable|boolean',
        ];
    }

    public function messages(): array {
        return [
            'brand_id.required' => 'La marca es obligatoria.',
            'brand_id.exists' => 'La marca seleccionada no existe.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'name.required' => 'El nombre del sabor es obligatorio.',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',];
    }
}
