<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mix extends Model {

    protected $table = 'mixes';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'name',
        'notes',
        'is_public',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function items() {
        return $this->hasMany(MixItem::class, 'mix_id','id');
    }

  
}
