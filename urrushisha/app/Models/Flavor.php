<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flavor extends Model {

    protected $table = 'flavors';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'description',
        'tobacco_type',
        'ingredients_text',
        'image_url',
        'created_by',
        'is_public',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function brand() {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function mixItems() {
        return $this->hasMany(MixItem::class);
    }

    public function favorites() {
        return $this->hasMany(Favorite::class);
    }
}
