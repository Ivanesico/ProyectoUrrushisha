<?php

namespace App\Http\Requests;

use App\Models\Flavor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlavorRequest extends FormRequest {

    private const GRAMS_MESSAGE = 'La cantidad debe ser un número entero de gramos entre 1 y 5000.';

    private const PRICE_MESSAGE = 'El precio debe ser un importe entre 0,01 y 9999,99 € con como mucho 2 decimales.';

    public function authorize(): bool {
        return auth()->check() && auth()->user()->is_admin;
    }

    /**
     * Spec 010: en los precios se admite coma decimal ("12,50") y se ignoran las filas vacías.
     * Solo se tocan los valores de texto: un array u otro tipo llega tal cual y falla la validación.
     */
    protected function prepareForValidation(): void {
        $prices = $this->input('prices');

        if (!is_array($prices)) {
            return;
        }

        $rows = [];

        foreach ($prices as $row) {
            if (is_array($row)) {
                $grams = $row['grams'] ?? null;
                $price = $row['price'] ?? null;

                $grams = is_string($grams) ? trim($grams) : $grams;
                $price = is_string($price) ? str_replace(',', '.', trim($price)) : $price;

                if (($grams === null || $grams === '') && ($price === null || $price === '')) {
                    continue;
                }

                $row = ['grams' => $grams, 'price' => $price];
            }

            $rows[] = $row;
        }

        $this->merge(['prices' => $rows]);
    }

    public function rules(): array {
        return [
            // Spec 010: precios por cantidad; sin consultas a la base de datos con estos valores.
            'prices' => 'nullable|array|max:10',
            'prices.*' => 'array',
            'prices.*.grams' => 'bail|required|integer|between:1,5000|distinct',
            'prices.*.price' => 'bail|required|numeric|between:0.01,9999.99|decimal:0,2',
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
            'prices.array' => 'Los precios deben enviarse como una lista.',
            'prices.max' => 'Puedes añadir como mucho 10 precios.',
            'prices.*.array' => 'Cada precio debe tener cantidad y precio.',
            'prices.*.grams.required' => 'Indica la cantidad de cada precio.',
            'prices.*.grams.integer' => self::GRAMS_MESSAGE,
            'prices.*.grams.between' => self::GRAMS_MESSAGE,
            'prices.*.grams.distinct' => 'No puedes repetir la misma cantidad.',
            'prices.*.price.required' => 'Indica el precio de cada cantidad.',
            'prices.*.price.numeric' => self::PRICE_MESSAGE,
            'prices.*.price.between' => self::PRICE_MESSAGE,
            'prices.*.price.decimal' => self::PRICE_MESSAGE,
        ];
    }
}
