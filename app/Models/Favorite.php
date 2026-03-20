<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model {

    protected $table = 'favorites';
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'int';
    protected $fillable = [
        'user_id',
        'flavor_id',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function flavor() {
        return $this->belongsTo(Flavor::class, 'flavor_id', 'id');
    }
}
