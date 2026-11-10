<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Spec 011: valoración de 1 a 5 estrellas de un usuario a un sabor.
 */
class FlavorRating extends Model {

    protected $table = 'flavor_ratings';
    protected $fillable = [
        'user_id',
        'flavor_id',
        'stars',
    ];
    protected $casts = [
        'stars' => 'integer',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function flavor() {
        return $this->belongsTo(Flavor::class);
    }

    // Media con un decimal (null sin valoraciones), igual en los tres motores.
    public static function roundedAverage($average): ?float {
        return $average === null ? null : round((float) $average, 1);
    }

    // Formato español de la media: "4,3".
    public static function formatAverage($average): string {
        return number_format((float) $average, 1, ',', '');
    }
}
