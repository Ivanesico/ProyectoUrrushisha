<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Flavor extends Model {

    use HasFactory;

    public const TOBACCO_TYPES = ['rubio', 'negro', 'herbal', 'base', 'rojo', 'sin_nicotina'];

    // Letras que el buscador ignora. Se aplica igual a lo que escribe el usuario y a la columna.
    private const SEARCH_REPLACEMENTS = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
    ];

    private const SEARCHABLE_COLUMNS = ['name', 'ingredients_text', 'tobacco_type'];

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

    public function getInitialAttribute(): string {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }

    public function getTobaccoTypeLabelAttribute(): ?string {
        return $this->tobacco_type ? self::tobaccoTypeLabel($this->tobacco_type) : null;
    }

    public static function tobaccoTypeLabel(string $type): string {
        return ucfirst(str_replace('_', ' ', $type));
    }

    public static function normalizeSearchTerm(string $term): string {
        return strtr(mb_strtolower($term), self::SEARCH_REPLACEMENTS);
    }

    /**
     * Coincidencia parcial que ignora mayúsculas, acentos y ñ en Postgres, MySQL y SQLite.
     * Sustituye también las mayúsculas acentuadas porque lower() no las trata igual en todos los motores.
     */
    public function scopeMatchingText(Builder $query, string $column, string $term): Builder {
        if (!in_array($column, self::SEARCHABLE_COLUMNS, true)) {
            throw new InvalidArgumentException("Columna de búsqueda no permitida: {$column}");
        }

        $expression = $query->getGrammar()->wrap($column);

        foreach (self::SEARCH_REPLACEMENTS as $letter => $replacement) {
            $expression = "replace({$expression}, '{$letter}', '{$replacement}')";
        }

        return $query->whereRaw("lower({$expression}) like ?", ['%' . self::normalizeSearchTerm($term) . '%']);
    }
}
