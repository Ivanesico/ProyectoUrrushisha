<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Spec 012: tipo de tabaco (Rubio, Negro, Sin nicotina…), gestionado desde el admin.
 */
class TobaccoType extends Model {

    protected $table = 'tobacco_types';
    protected $fillable = [
        'name',
    ];

    public function flavors() {
        return $this->hasMany(Flavor::class, 'tobacco_type_id', 'id');
    }
}
