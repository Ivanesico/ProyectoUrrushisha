<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Mix extends Model {

    use HasFactory;

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

    // Spec 008: mezcla pública de la que esta es copia (null si no es copia o si la original se borró).
    public function source() {
        return $this->belongsTo(Mix::class, 'source_mix_id', 'id');
    }

    /**
     * Spec 007: mezclas públicas con todos sus sabores públicos, sin las de $excludeUserId,
     * filtradas por nombre (`name`), sabor que contienen (`flavor`) y categoría (`category_id`).
     * Un id no válido o un texto que llega como array dan 0 resultados sin llegar a la consulta.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopePublicListing(Builder $query, array $filters, ?int $excludeUserId = null): Builder {
        $query->where('mixes.is_public', true)
                ->whereDoesntHave('items.flavor', fn (Builder $flavor) => $flavor->where('flavors.is_public', false));

        if ($excludeUserId !== null) {
            $query->where('mixes.user_id', '!=', $excludeUserId);
        }

        $isEmpty = fn ($value) => $value === null || (is_string($value) && trim($value) === '');

        $name = $filters['name'] ?? null;
        if (!$isEmpty($name)) {
            if (is_string($name)) {
                $expression = Flavor::normalizedExpression($query->getGrammar()->wrap('mixes.name'));
                $query->whereRaw("{$expression} like ?", ['%' . Flavor::normalizeSearchTerm($name) . '%']);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $flavorName = $filters['flavor'] ?? null;
        if (!$isEmpty($flavorName)) {
            if (is_string($flavorName)) {
                $query->whereHas('items.flavor', fn (Builder $flavor) => $flavor->matchingText('name', $flavorName));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $categoryId = $filters['category_id'] ?? null;
        if (!$isEmpty($categoryId)) {
            $id = is_string($categoryId) || is_int($categoryId)
                ? filter_var($categoryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : false;

            if ($id === false) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('items.flavor', fn (Builder $flavor) => $flavor->where('flavors.category_id', $id));
            }
        }

        return $query;
    }

    /**
     * Spec 007: orden de la lista pública, con desempate por id para que la paginación no repita ni pierda mezclas.
     */
    public function scopeSortedForListing(Builder $query, string $sort): Builder {
        $grammar = $query->getGrammar();
        $id = $grammar->wrap('mixes.id');

        if ($sort === 'name_asc' || $sort === 'name_desc') {
            $direction = $sort === 'name_asc' ? 'asc' : 'desc';
            $name = Flavor::normalizedExpression($grammar->wrap('mixes.name'));

            return $query->orderByRaw("{$name} {$direction}")->orderByRaw("{$id} {$direction}");
        }

        // Sin fecha al final en los tres motores (Postgres pone los nulos primero con desc).
        $createdAt = $grammar->wrap('mixes.created_at');

        return $query->orderByRaw("case when {$createdAt} is null then 1 else 0 end")
                ->orderByRaw("{$createdAt} desc")
                ->orderByRaw("{$id} desc");
    }

  
}
