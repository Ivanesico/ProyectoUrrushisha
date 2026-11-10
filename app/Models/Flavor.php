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

    // Órdenes del catálogo de la portada; el primero es el de por defecto. El admin añade los de ID.
    public const CATALOG_SORTS = [
        'recent' => 'Añadidos recientemente',
        'name_asc' => 'Nombre (A-Z)',
        'name_desc' => 'Nombre (Z-A)',
        'brand' => 'Marca',
        'category' => 'Categoría',
    ];

    public const ADMIN_SORTS = self::CATALOG_SORTS + [
        'id_asc' => 'ID (menor primero)',
        'id_desc' => 'ID (mayor primero)',
    ];

    // Filtros de texto: clave del filtro => columna en la que se busca.
    private const TEXT_FILTERS = ['name' => 'name', 'ingredient' => 'ingredients_text', 'tobacco_type' => 'tobacco_type'];

    // Datos incompletos: clave del filtro => columna que está vacía (nula o '').
    private const MISSING_FILTERS = ['image' => 'image_url', 'description' => 'description'];

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

        $expression = self::normalizedExpression($query->getGrammar()->wrap($column));

        return $query->whereRaw("{$expression} like ?", ['%' . self::normalizeSearchTerm($term) . '%']);
    }

    /**
     * Filtros del catálogo y del admin. Solo se aplican las claves presentes en $filters, así cada
     * pantalla decide cuáles admite. Un id no válido o un texto que llega como array dan 0 resultados
     * sin llegar a la consulta (Postgres los rechazaría); una visibilidad o un "incompleto" desconocidos se ignoran.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilteredBy(Builder $query, array $filters): Builder {
        $isEmpty = fn ($value) => $value === null || (is_string($value) && trim($value) === '');

        foreach (['brand_id', 'category_id'] as $column) {
            $value = $filters[$column] ?? null;

            if ($isEmpty($value)) {
                continue;
            }

            $id = is_string($value) || is_int($value)
                ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : false;

            if ($id === false) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where("flavors.{$column}", $id);
            }
        }

        foreach (self::TEXT_FILTERS as $filter => $column) {
            $term = $filters[$filter] ?? null;

            if ($isEmpty($term)) {
                continue;
            }

            if (is_string($term)) {
                $query->matchingText($column, $term);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $visibility = $filters['visibility'] ?? null;
        if ($visibility === 'public' || $visibility === 'hidden') {
            $query->where('flavors.is_public', $visibility === 'public');
        }

        $missing = $filters['missing'] ?? null;
        if (is_string($missing) && array_key_exists($missing, self::MISSING_FILTERS)) {
            $column = 'flavors.' . self::MISSING_FILTERS[$missing];
            $query->where(fn (Builder $empty) => $empty->whereNull($column)->orWhere($column, ''));
        }

        return $query;
    }

    /**
     * Ordena el catálogo en SQL, siempre con desempate final por id para que la paginación
     * no repita ni pierda sabores. Los nulos van al final con CASE, que funciona igual en
     * Postgres (nulos primero en desc) y en MySQL (nulos primero en asc).
     */
    public function scopeSortedBy(Builder $query, string $sort): Builder {
        $grammar = $query->getGrammar();
        $id = $grammar->wrap('flavors.id');
        $name = self::normalizedExpression($grammar->wrap('flavors.name'));

        switch ($sort) {
            case 'recent':
                $createdAt = $grammar->wrap('flavors.created_at');

                return $query->orderByRaw("case when {$createdAt} is null then 1 else 0 end")
                        ->orderByRaw("{$createdAt} desc")
                        ->orderByRaw("{$id} desc");

            case 'id_asc':
            case 'id_desc':
                // El id nunca es nulo y no se repite: no necesita desempate.
                return $query->orderByRaw($id . ($sort === 'id_asc' ? ' asc' : ' desc'));

            case 'name_asc':
            case 'name_desc':
                $direction = $sort === 'name_asc' ? 'asc' : 'desc';

                return $query->orderByRaw("{$name} {$direction}")->orderByRaw("{$id} asc");

            case 'brand':
            case 'category':
                // Subconsulta y no join: con un join, "name" sería ambiguo en matchingText.
                $table = $sort === 'brand' ? 'brands' : 'categories';
                $foreignKey = $sort === 'brand' ? 'brand_id' : 'category_id';
                $relatedName = '(select ' . $grammar->wrap("{$table}.name") . ' from ' . $grammar->wrapTable($table)
                        . ' where ' . $grammar->wrap("{$table}.id") . ' = ' . $grammar->wrap("flavors.{$foreignKey}") . ')';

                return $query->orderByRaw("case when {$relatedName} is null then 1 else 0 end")
                        ->orderByRaw(self::normalizedExpression($relatedName) . ' asc')
                        ->orderByRaw("{$name} asc")
                        ->orderByRaw("{$id} asc");
        }

        throw new InvalidArgumentException("Orden no permitido: {$sort}");
    }

    /**
     * Expresión SQL en minúsculas y sin acentos. Sustituye también las mayúsculas acentuadas
     * porque lower() no las trata igual en todos los motores.
     */
    private static function normalizedExpression(string $expression): string {
        foreach (self::SEARCH_REPLACEMENTS as $letter => $replacement) {
            $expression = "replace({$expression}, '{$letter}', '{$replacement}')";
        }

        return "lower({$expression})";
    }
}
