<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixItem extends Model
{
    protected $table = 'mix_items';
    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'mix_id',
        'flavor_id',
        'ratio',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function mix()
    {
        return $this->belongsTo(Mix::class, 'mix_id', 'id');
    }

    public function flavor()
    {
        return $this->belongsTo(Flavor::class, 'flavor_id', 'id');
    }
}
