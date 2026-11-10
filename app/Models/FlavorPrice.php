<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Spec 010: precio de un sabor para una cantidad en gramos (p. ej. 50 g · 12,50 €).
 */
class FlavorPrice extends Model {

    protected $table = 'flavor_prices';
    protected $fillable = [
        'flavor_id',
        'grams',
        'price',
    ];
    protected $casts = [
        'grams' => 'integer',
        'price' => 'decimal:2',
    ];

    public function flavor() {
        return $this->belongsTo(Flavor::class);
    }

    // Formato español: "1.234,50 €".
    public static function formatPrice($price): string {
        return number_format((float) $price, 2, ',', '.') . ' €';
    }
}
