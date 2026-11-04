# Plan — Spec 001: Verificación de la aplicación y corrección de errores

Parte de la spec aprobada (`spec.md`). Cada RF se cubre según su marca en la spec: [T], [R], [N] o [U].

## 1. Archivos

### Nuevos
| Archivo | Responsabilidad | RF |
|---|---|---|
| `database/migrations/2026_11_03_000000_add_rojo_to_flavors_tobacco_type.php` | Amplía la lista a 5 valores en los 3 motores; la reversión la devuelve a 4 | RF-50 a 53 |
| `database/seeders/LocalTestUsersSeeder.php` | Copia exacta de `.claude/database/seeders/LocalTestUsersSeeder.php` (el original no se toca) | RF-56, 57 |
| `database/seeders/LocalTestDataSeeder.php` | Datos de prueba locales, sin borrar nada y sin duplicar | RF-65, 66 |
| `database/factories/BrandFactory.php`, `CategoryFactory.php`, `FlavorFactory.php`, `MixFactory.php` | Datos de los tests | Todos los [T] |
| `app/Http/Requests/StoreMixRequest.php` | Validación de crear y editar mezclas | RF-21 a 24, 26, 58, 59, 54, 55, 60 |
| `resources/views/brands/show.blade.php` | Detalle público de marca | RF-14, 15 |
| `resources/views/errors/403.blade.php`, `errors/404.blade.php` | Páginas de error en español | RF-62, 60 |
| `resources/views/vendor/pagination/bootstrap-5.blade.php` | Paginación con textos literales en español | RF-63, 60 |
| `tests/Feature/TobaccoTypeMigrationTest.php` | Migración y reversión con datos | RF-50 a 52 |
| `tests/Feature/LocalSeedersTest.php` | Seeders locales | RF-56, 57, 65, 66 |
| `tests/Feature/AuthTest.php` | Acceso | RF-1 a 9, y 54, 60 (login y registro) |
| `tests/Feature/ErrorPagesTest.php` | Páginas 403 y 404, ids en la URL | RF-62, 60, 55 (URL) |
| `tests/Feature/CatalogTest.php` | Catálogo | RF-10 a 12e, 61, 63, 60 |
| `tests/Feature/BrandTest.php` | Marcas públicas | RF-13, 14 |
| `tests/Feature/FavoriteTest.php` | Favoritos | RF-16 a 19 |
| `tests/Feature/MixTest.php` | Mezclas | RF-20 a 34, 58, y 54, 55, 60 (mezclas) |
| `tests/Feature/AdminAccessTest.php` | Acceso de admin | RF-35 a 37 |
| `tests/Feature/AdminBrandCategoryTest.php` | Marcas y categorías | RF-38 a 42, y 54, 55, 60 |
| `tests/Feature/AdminFlavorTest.php` | Sabores e imágenes | RF-43 a 49, 64, 68, y 54, 55, 60 |

### Modificados
| Archivo | Cambio | RF |
|---|---|---|
| `database/migrations/2026_03_05_175025_create_flavors_table.php` | Solo se revierte a la versión de Git, con las condiciones de la T1 | RF-53 |
| `tests/Feature/ExampleTest.php` | La aserción espera la redirección al login; nada más | RF-7 |
| `routes/web.php` | Patrones de id al principio del archivo | RF-55 |
| `app/Http/Controllers/FlavorController.php` | Solo `index`: filtros seguros y `$filters` para la vista | RF-11, 12, 12b, 12e |
| `app/Http/Controllers/MixController.php` | Usa `StoreMixRequest`, transacción, lista de sabores en `edit` | RF-27, 29, 59 |
| `app/Http/Controllers/Admin/BrandController.php` | `show` con 404, borrado bloqueado, mensaje de edición | RF-37, 38, 41 |
| `app/Http/Controllers/Admin/CategoryController.php` | `show` con 404, corrige `StoreCategoryRequestRequest`, mensaje de edición | RF-37, 38 |
| `app/Http/Controllers/Admin/FlavorController.php` | `show` con 404, borrado bloqueado | RF-37, 49 |
| `app/Http/Requests/LoginRequest.php`, `RegisterRequest.php` | Mensajes de la tabla de RF-54 | RF-54, 60 |
| `app/Http/Requests/StoreBrandRequest.php`, `StoreCategoryRequest.php` | Límite, `unique` que ignora el registro actual, mensajes | RF-39, 40, 54 |
| `app/Http/Requests/StoreFlavorRequest.php` | `tobacco_type`, límites, regla de imagen, ids, mensajes | RF-45 a 47, 54, 55, 64 |
| `app/Models/Brand.php`, `Category.php`, `Mix.php` | `HasFactory` | Tests |
| `app/Models/Flavor.php` | `HasFactory`, constante `TOBACCO_TYPES`, scope de búsqueda, atributo `initial` | RF-12, 12d, 45 |
| `app/Models/MixItem.php` | Cast `ratio` a `integer` | RF-30 |
| `resources/views/login.blade.php` | Quita el `@include('js.login')` y su `@push` vacío; `resources/views/js/login.blade.php` no se borra. `mx-0` en `.row` para evitar el scroll horizontal (hallazgo de la validación) | RF-9, 67, RNF-7 |
| `resources/views/register.blade.php` | Muestra errores y conserva nombre y email. `mx-0` en `.row` (hallazgo de la validación) | RF-3, RNF-7 |
| `resources/views/index.blade.php` | Usa `$filters`, placeholder válido, inicial, enlace a `mixes.create`. `.badge-soft` puede partirse en varias líneas (hallazgo de la validación) | RF-12c, 12d, 12e, 61, 67, RNF-7 |
| `resources/views/favorites/index.blade.php` | Inicial, enlace a `mixes.create`. `.badge-soft` puede partirse en varias líneas (hallazgo de la validación) | RF-12d, 61, 67, RNF-7 |
| `resources/views/brands/index.blade.php` | Listado de marcas, cada una enlazada a su detalle | RF-13, 15 |
| `resources/views/mixes/index.blade.php` | Inicial | RF-12d |
| `resources/views/mixes/create.blade.php`, `mixes/edit.blade.php` | Errores y valores conservados, filas nuevas | RF-27, 31 |
| `resources/views/admin/brands/index.blade.php`, `admin/flavors/index.blade.php` | Muestran `session('error')` | RF-41, 49 |
| `resources/views/admin/categories/index.blade.php` | Usa `$category` en vez de `$flavor`, que da error de servidor con cualquier categoría en la lista | RF-36, 38 |
| `resources/views/admin/flavors/edit.blade.php` | Solo la casilla pública | RF-68 |

No se tocan `.env`, `composer.json`, `package.json`, `CLAUDE.md` ni los restos que lista CLAUDE.md, ni se añade carpeta `lang/`.

## 2. Rutas
No hay rutas nuevas ni cambia ningún middleware ni nombre.

Al principio de `routes/web.php`, antes de definir rutas, se añade `Route::pattern` para `mix`, `flavor`, `brand` y `category` con `[1-9][0-9]{0,17}` (de 1 a 18 dígitos). Un id de 19 dígitos es válido según la spec, pero no puede existir en la práctica, y responde igualmente 404. (RF-55)

**RF-37:** los `Route::resource` de admin se mantienen con `show`, y el método `show` de los 3 controladores responde `abort(404)`. `->except('show')` no sirve: las rutas PUT y DELETE comparten URL, y un GET daría 405, no 404. El middleware `admin` sigue dando 403 antes a quien no es admin.

## 3. Base de datos

### Migración de `tobacco_type` (RF-50 a 53)
Solo cambia la restricción de la lista. **Efecto en producción**: los valores actuales están dentro de los 4 antiguos, que siguen siendo válidos; no se modifica ninguna fila.

Comprobado en `vendor/`:
- `Migrator.php:448` solo abre transacción si la gramática la admite. `PostgresGrammar` tiene `$transactions = true`; las de SQLite y MySQL, no.
- Por eso la migración **mantiene `$withinTransaction` en su valor por defecto**: Postgres corre en transacción y SQLite no. Sustituye a la idea de `$withinTransaction = false` que mencionaba como ejemplo la spec.

`up()` y `down()` eligen por `DB::getDriverName()`. Las listas se escriben literalmente en la migración, sin usar la constante del modelo. Un driver desconocido lanza una excepción.

**`mysql`, `mariadb` y `sqlite`:** `Schema::table` con `enum('tobacco_type', [lista])->nullable()->change()`.
- En MySQL redefine la columna sin tocar filas.
- En SQLite reconstruye la tabla (`SQLiteGrammar::compileAlter`, líneas 357-370). Conserva claves foráneas e índices porque los lee en `BlueprintState`, y desactiva las claves foráneas con `PRAGMA`, que funciona porque el Migrator no abre transacción en SQLite.

**`pgsql`** (dentro de la transacción del Migrator):
1. Busca en el catálogo las restricciones reales de la columna:
   ```sql
   SELECT c.conname
   FROM pg_constraint c
   JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
   WHERE c.conrelid = 'flavors'::regclass AND c.contype = 'c' AND a.attname = 'tobacco_type'
   ```
2. Elimina cada una con `ALTER TABLE flavors DROP CONSTRAINT "<nombre obtenido>"`. **No se usa `IF EXISTS`**: el nombre viene del catálogo, y si algo falla debe dar error.
3. Crea `flavors_tobacco_type_check` con `CHECK (tobacco_type IN (...))`. `NULL` sigue permitido, porque `CHECK` lo acepta.
4. **Se comprueba a sí misma**: repite la consulta con `pg_get_constraintdef(c.oid)`. Si no hay exactamente una restricción, o `up()` no encuentra `'rojo'`, o `down()` lo encuentra, lanza una excepción. La transacción se deshace y `rojo` no puede quedar fallando en silencio.

No hay `UPDATE` ni `DELETE`, ni cambia el tipo de la columna.

**`down()`:** antes de nada, cuenta los sabores con `rojo`. Si hay N > 0, lanza `RuntimeException` con el texto exacto `No se puede revertir: hay N sabores con tipo de tabaco rojo`, sin tocar nada. Si no hay, repite el mismo proceso con los 4 valores.

### Cómo se revisa la parte de Postgres (ningún test la ejecuta)
`pdo_pgsql` está desactivado y activarlo está en la lista de consulta de la spec. Hay 3 controles:
1. **Reviewer, en la validación** ("revisado, no ejecutado"): comprueba los 4 pasos anteriores y estos puntos: no hay `IF EXISTS`; el nombre sale de la consulta; no hay `$withinTransaction = false`; no hay `UPDATE` ni `DELETE`; el nombre eliminado va entre comillas dobles; ninguna sentencia de Postgres llega a otro motor.
2. **Usuario, antes del push** (lo pide el coordinador en el cierre; ningún agente toca producción). En la consola de Postgres de Railway:
   - Solo lectura: `SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid = 'flavors'::regclass AND contype = 'c';`
   - Ensayo que no deja cambios: `BEGIN;`, luego `ALTER TABLE flavors DROP CONSTRAINT "<nombre obtenido>";`, luego `ALTER TABLE flavors ADD CONSTRAINT flavors_tobacco_type_check CHECK (tobacco_type IN ('rubio','negro','herbal','rojo','sin_nicotina'));` y por último `ROLLBACK;`.
3. **Usuario, después del push**: guardar un sabor `rojo` en producción (criterio de finalización de la spec).

**Si falla en producción:** la transacción se deshace, sin cambios en los datos, pero `migrate --force && ... serve` impide arrancar. Se avisa antes de pedir el push.

### Modelos
- `Flavor`: constante `TOBACCO_TYPES` con los 5 valores, usada por la validación.
- `MixItem`: `casts` añade `'ratio' => 'integer'`. Solo cambia cómo se lee: `50.00` pasa a `50`, sin tocar datos guardados.
- `fillable` y relaciones no cambian. `mix_items.role` no se toca.

### Seeders
- Ambos solo actúan en `local` y usan `updateOrCreate` o `firstOrCreate`.
- `Favorite` no tiene `id`: usar `firstOrCreate`, no `save()` sobre un registro existente.
- `LocalTestDataSeeder` crea los datos de RF-65. Si faltan los usuarios de `LocalTestUsersSeeder`, muestra un error en español y no crea nada.

## 4. Lógica

### Buscador (RF-12)
En `Flavor` se añade un scope de búsqueda y un método público de normalización:
- **Mapa único de 14 sustituciones**: `á é í ó ú ü ñ` y `Á É Í Ó Ú Ü Ñ` pasan a `a e i o u u n`.
- **Lo que escribe el usuario** se normaliza en PHP con `mb_strtolower` y ese mapa.
- **La columna** se compara con `lower(replace(replace(... columna ...)))  LIKE ?`, con `replace()` anidados generados desde el mismo mapa y el parámetro `%término%`.
- **Columnas**: solo `name`, `ingredients_text` y `tobacco_type`, en una lista fija. Nunca una columna que venga de la petición.

`replace()`, `lower()` y `LIKE` existen en Postgres, MySQL y SQLite; no hay extensión, migración ni dato nuevo. Sustituir también las mayúsculas hace que el resultado no dependa de la configuración regional de Postgres ni del `lower()` de SQLite, que solo trata ASCII.

### Filtros del catálogo (`FlavorController@index`)
- `brand_id` y `category_id`: si están rellenos, se validan con `filter_var(..., FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])`. Si son válidos, se filtra con el valor convertido a entero; si no (incluidos arrays y desbordamientos), `whereRaw('1 = 0')`. El valor no válido nunca llega a la consulta. (RF-12b)
- Filtros de texto: si llegan como no texto, `whereRaw('1 = 0')`. (RF-12e)
- La vista recibe `$filters`, con textos seguros (`''` si llegó un array), y no vuelve a usar `request()` en esos campos. Si no, `value="{{ request('name') }}"` falla con un array.

### `StoreMixRequest` (RF-59; se usa en `store` y `update`)
- `authorize()`: si la ruta trae `mix`, el usuario debe ser su dueño. Así el 403 llega antes que la validación. (RF-33)
- Reglas:
  - `name`: `required|string|max:180`
  - `notes`: `nullable|string`
  - `flavors`: `required|array|min:1`
  - `flavors.*`: `bail|required|integer|min:1|distinct|exists:flavors,id`
  - `ratios`: `required|array|min:1`
  - `ratios.*`: `required|integer|min:1|max:100`
- `bail` es imprescindible: sin él, `exists` consultaría `abc` y fallaría en Postgres.
- Comprobaciones en `after()`, solo si las reglas anteriores han pasado:
  1. Las claves de `flavors` y `ratios` coinciden. (RF-23)
  2. La suma es exactamente 100. (RF-21)
  3. Todo sabor está en favoritos o, si es una edición, en los ítems guardados de la mezcla. (RF-24 a 27)
- `messages()` con la tabla de RF-54, literal en español.

### `MixController`
- `store` y `update` usan `StoreMixRequest` y guardan dentro de `DB::transaction`. (RF-29)
- `update` deja de comprobar el dueño a mano; `edit` y `destroy` lo siguen haciendo.
- `edit` pasa como `$flavors` los favoritos más los sabores ya presentes, con `brand` y por nombre. La misma lista alimenta las filas y la plantilla de fila nueva. (RF-27)

### Form Requests de admin
- Marca: `bail|required|string|max:120|` más `Rule::unique` con `ignore($this->route('brand'))`. Categoría, igual con `max:80`. (RF-39, 40)
- Sabor:
  - `brand_id` y `category_id`: `bail|required|integer|min:1|exists:...`
  - `name`: `required|string|max:180`
  - `description`: `nullable|string`
  - `tobacco_type`: `nullable` y `Rule::in(Flavor::TOBACCO_TYPES)`; el mensaje enumera los 5 valores.
  - `ingredients_text`: `nullable|string|max:500`
  - `image`: `nullable|image|mimes:jpg,jpeg,png,webp|max:2048`, que pasa de `messages()` a `rules()`.
  - `is_public`: `nullable|boolean`
  - Se elimina la regla `image_url`, que no corresponde a ningún campo.
- RF-64: mensaje `image.uploaded`. Laravel añade ese fallo cuando la subida no es válida y el campo tiene reglas de archivo (`Validator.php:705-708`).
- Todos los `messages()` son literales, así que no dependen de `APP_LOCALE`. (RF-60)

### Controladores de admin
- Marca con sabores (`$brand->flavors()->exists()`) o sabor en mezclas (`$flavor->mixItems()->exists()`): no se borra y se redirige al listado con `with('error', '...')` en español. (RF-41, 49)
- Al editar, los mensajes dicen "actualizada". (RF-38)
- `StoreCategoryRequestRequest` pasa a `StoreCategoryRequest`.
- El almacenamiento de imágenes no cambia. (RF-48)

## 5. Interfaz
Todo con clases de Bootstrap, sin CSS nuevo salvo lo que ya tenga cada vista.

- **`brands/index` y `brands/show`**: `container`, `list-group` o `row`/`col`, sin anchos fijos. Muestran un mensaje si no hay marcas o sabores. Ninguna pantalla existente enlaza con ellas. (RF-13 a 15)
- **`errors/403` y `errors/404`**: extienden `layouts.app`. Deben funcionar sin sesión iniciada, porque una URL sin ruta no pasa por el middleware de sesión. Texto exacto de RF-62.
- **Paginación**: copia del `bootstrap-5` de Laravel con literales: "Anterior", "Siguiente" y "Mostrando X a Y de Z resultados", también en los `aria-label`. Laravel la carga sola desde `resources/views/vendor/pagination`.
- **`mixes/create`**:
  - Muestra errores como `mixes/edit`.
  - Las filas salen de `old('flavors', [''])`, con su porcentaje `old("ratios.$i")`.
  - Conserva nombre, notas y casilla.
  - El JS de añadir fila sigue funcionando, porque clona y vacía la primera.
- **`mixes/edit`**:
  - Las filas salen de `old()` si hay errores y, si no, de los ítems.
  - **La casilla pública usa `$errors->any() ? old('is_public') : $mix->is_public`**: con `old('is_public', $mix->is_public)`, una casilla desmarcada volvería a salir marcada, porque el navegador no envía el campo.
- **`index` y `favorites/index`**: inicial con `$flavor->initial` (`mb_strtoupper(mb_substr(...))`) y "Crear mezcla" a `mixes.create`. `mixes/index` usa el mismo atributo.
- **`admin/flavors/edit`**: la casilla pública usa `$errors->any() ? old('is_public') : $flavor->is_public`, con el mismo criterio que `mixes/edit`. Con `old('is_public', $flavor->is_public)`, una casilla desmarcada vuelve a salir marcada tras un rechazo. Es el único cambio en esta vista. (RF-68)
- **Móvil**: los flujos de RF-67 los comprueba el reviewer a 375 px. Marcas, errores y administración solo reciben la comprobación rápida del implementer y la revisión de clases (RNF-7).

## 6. Permisos
No cambia ningún permiso.

| | Sin sesión | Registrado | Admin |
|---|---|---|---|
| Login y registro | Sí | Redirige al catálogo | Redirige al catálogo |
| Catálogo, marcas, favoritos | Redirige al login | Sí | Sí |
| Mezclas propias | Redirige al login | Sí | Sí |
| Mezclas ajenas | Redirige al login | 403 | 403 |
| Rutas `/admin` | Redirige al login | 403 | Sí |

Se comprueba en el servidor: middleware `auth` y `admin`, `authorize()` de los Form Requests y `abort(403)` en `MixController`.

## 7. Decisiones técnicas
| Decisión | Alternativa descartada y por qué |
|---|---|
| `replace()` anidado con `lower()` para el buscador | `unaccent`: exige `CREATE EXTENSION`, sin permisos comprobados, y no existe en MySQL ni SQLite. Columna normalizada: exige migración y rellenar datos. `translate()`: no existe en MySQL. `ilike`: rompe MySQL y SQLite. `whereLike`: no ignora acentos en Postgres ni SQLite. |
| Restricción localizada en `pg_constraint` | `DROP CONSTRAINT IF EXISTS flavors_tobacco_type_check`: con otro nombre, no da error y deja activa la lista antigua. |
| Autocomprobación en la rama de Postgres | Confiar solo en la revisión: esa rama no se ejecuta en ningún test. |
| Mantener `$withinTransaction` por defecto | `false` con transacción explícita en Postgres: no aporta nada, porque el Migrator ya no abre transacción en SQLite y sí en Postgres. |
| `->change()` en MySQL y SQLite, SQL propio solo en Postgres | SQL propio en los 3: más código sin ventaja. `->change()` en Postgres: genera SQL no válido. |
| Un solo `StoreMixRequest`, igual que los demás Form Requests del proyecto | Dos clases: duplicaría reglas; RF-59 pide las mismas en ambos casos. |
| `show` con `abort(404)` en admin | `->except('show')`: daría 405, no 404. |
| `Route::pattern` de hasta 18 dígitos | Comprobar en cada controlador: se repite, y el enlace de modelo consultaría antes con el valor no válido. |
| Vista de paginación propia con literales | Carpeta `lang/es` y fijar el idioma: cambiaría textos de toda la aplicación, más allá de lo pedido. |
| Vistas `errors/403` y `errors/404` con literales | Traducir con `__()`: dependería de `APP_LOCALE`. |
| Bloquear borrados comprobando antes con `exists()` | Capturar la `QueryException`: el mensaje depende del motor y, en Postgres, deja abortada una transacción. |

## 8. Estrategia de tests (`php artisan test`, SQLite en memoria)

Clasificación según "Cómo se escriben los tests" de la spec: **corrección** (rojo primero), **caracterización** (nace en verde) y **no reproducible en SQLite** (se demuestra con [R] o [N]).

| Archivo | Tarea | RF | Notas |
|---|---|---|---|
| `TobaccoTypeMigrationTest` | T1 | 50, 51, 52 | Ver abajo |
| `LocalSeedersTest` | T1 | 56, 57, 65, 66 | Entorno simulado con `$this->app['env'] = 'local'` |
| `ExampleTest` | T1 | 7 | Excepción expresa de la spec |
| `AuthTest` | T2 | 1 a 9; 54, 60 | RF-9 con `assertDontSee('registerForm')` |
| `ErrorPagesTest` | T2 | 62, 60, 55 (URL) | Con `DB::listen`: el id no válido no llega a una consulta |
| `CatalogTest` | T3 | 10 a 12e, 61, 63, 60 | RF-12b con `DB::listen` |
| `BrandTest` | T3 | 13, 14 | |
| `FavoriteTest` | T3 | 16 a 19 | |
| `MixTest` | T4 | 20 a 34, 58; 54, 55, 60 | RF-29 forzando una excepción al crear el segundo `MixItem` |
| `AdminAccessTest` | T5 | 35 a 37 | Todas las rutas y métodos |
| `AdminBrandCategoryTest` | T5 | 38 a 42; 54, 55, 60 | |
| `AdminFlavorTest` | T5 | 43 a 49, 64, 68; 54, 55, 60 | Sin GD, ver abajo |

Todos usan `RefreshDatabase` y factories, salvo `TobaccoTypeMigrationTest` y `ExampleTest`. `RF-60` se prueba fijando `config(['app.locale' => 'en'])` y `app()->setLocale('en')`.

**`TobaccoTypeMigrationTest`:** es la única excepción a `RefreshDatabase`, porque la reconstrucción de SQLite necesita estar fuera de una transacción. Dentro de una, el `PRAGMA` se ignora y `DROP TABLE` borraría favoritos o fallaría por `mix_items`.
- **Protección obligatoria:** `setUp()` comprueba que el driver es `sqlite` y la base es `:memory:`, y si no, el test falla antes de ejecutar nada. Este test hace `migrate:rollback`, y nunca debe llegar al MySQL local.
- Cada test: `migrate` en su base en memoria; `migrate:rollback --step=1` para volver a 4 valores; inserta filas en `brands`, `categories`, `flavors`, `favorites`, `mixes` y `mix_items`; ejecuta `migrate`; compara todas las filas antes y después.
- Después comprueba: `rojo` se acepta; un valor fuera de la lista da `QueryException`; `PRAGMA foreign_keys` es 1; borrar un sabor usado en `mix_items` falla; borrar un sabor solo favorito borra su favorito; `is_public` conserva su valor por defecto.
- RF-52: ejecuta `down()` de la migración, cargada con `require`. Sin `rojo` vuelve a 4 valores con todas las filas intactas. Con `rojo`, comprueba el mensaje exacto con N y que nada cambia.

**Imágenes sin GD** (no se usa `UploadedFile::fake()->image()`), con `Storage::fake('public')`:
- `fake()->create('foto.jpg', 2048, 'image/jpeg')` se acepta; 2049 KB se rechaza; `create('doc.pdf', 10, 'application/pdf')` se rechaza.
- Contenido real: `new UploadedFile($tmp, ..., null, true)` con los bytes de un PNG válido escritos desde base64 se acepta; con texto y nombre `.jpg` se rechaza.
- RF-64: `new UploadedFile($tmp, 'foto.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true)`.

**Los 13 tests omitidos** (excepción expresa de la spec; los errores A y B siguen en la aplicación):

| Archivo | Método | Casos | Tests |
|---|---|---|---|
| `AdminBrandCategoryTest` | `test_an_array_as_name_does_not_break_the_form_when_creating` | "marca", "categoría" | 2 |
| `AdminBrandCategoryTest` | `test_an_array_as_name_does_not_break_the_form_when_updating` | "marca", "categoría" | 2 |
| `AdminFlavorTest` | `test_an_array_as_tobacco_type_does_not_break_the_forms` | (sin casos) | 1 |
| `AdminFlavorTest` | `test_the_create_form_shows_the_error_without_a_server_error` | "nombre como array", "descripción como array", "ingredientes como array", "público no booleano" | 4 |
| `AdminFlavorTest` | `test_the_edit_form_shows_the_error_without_a_server_error` | "nombre como array", "descripción como array", "ingredientes como array", "público no booleano" | 4 |

- Se marcan con `markTestSkipped` y el motivo, y sus aserciones no cambian.
- La omisión se aplica **caso por caso**: los demás casos de `invalidFields` en esos dos métodos de `AdminFlavorTest` se siguen ejecutando.
- La suite debe terminar con 0 fallidos y exactamente estos 13 omitidos (RNF-1).

**Solo [R] o [N]:** RF-15, 53, 59, 67, los RNF de revisión y la parte de Postgres de RF-12, 12b, 51, 52 y 55.

### Checklist de Postgres y migraciones
- `like`: resuelto con `lower()` en ambos lados (RF-12). Ninguna búsqueda queda con `like` directo.
- SQL de MySQL: ninguno. `replace` y `lower` son comunes; `whereRaw('1 = 0')` es estándar. El SQL de Postgres solo corre en su rama.
- Migraciones existentes: la original vuelve a la versión de Git; no se modifica ninguna.
- Migración nueva: compatible con los datos actuales, sin columnas nuevas.
- Listas cerradas: `Rule::in` evita valores fuera de `tobacco_type`. RF-65 guarda `rojo` solo tras aplicar la migración.
- Ids: `bail`, `integer` y `Route::pattern` evitan consultas con valores que Postgres rechaza.
- Longitudes: la validación coincide con las columnas (RF-40, 46, 58).

## 9. Secuencia de trabajo fuera de las tareas

### Preparación del entorno (la hace el usuario, no un implementer)
**Cuándo:** al terminar la T5 y antes de lanzar la validación. El coordinador se lo pide al usuario, en este orden:
1. `php artisan migrate`
2. `php artisan db:seed --class=LocalTestUsersSeeder`
3. `php artisan db:seed --class=LocalTestDataSeeder`
4. `php artisan serve`

El usuario informa del resultado (evidencia de los [U] de RF-51, 56 y 65) y entonces entra el reviewer. Las comprobaciones rápidas del implementer no lo necesitan: si el servidor no responde, se las salta y lo indica.

### Commit de cierre
- Se añaden **solo por ruta explícita** los archivos de la sección 1 de este plan (salvo la migración original) y `specs/001-verificacion-y-correccion/`. Nunca `git add -A` ni `git add .`.
- **Quedan fuera:**
  - `database/migrations/2026_03_05_175025_create_flavors_table.php`: revertido, no debe aparecer en `git status`. Si aparece, se para.
  - `nbproject/private/private.xml`.
  - `.env`, que ya está en `.gitignore`.
  - `.claude/`, `.mcp.json`, `CLAUDE.md` y `docs/`: estaban sin seguimiento antes de este flujo y no son suyos. El original del seeder en `.claude/` también queda fuera.
  - `MEMORY.md`: por decisión del usuario, se actualiza en local y queda fuera del commit.
- Antes del commit: `git status` y `git diff` comparados con esta lista.
- Antes de pedir el push: avisar de la migración y de que la aplicación no arranca si falla, y ofrecer los controles de Postgres de la sección 3.
- En el cierre se propone actualizar CLAUDE.md (5 valores de `tobacco_type`).
