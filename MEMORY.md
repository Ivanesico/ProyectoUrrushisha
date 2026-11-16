# MEMORY.md — UrruShisha

Memoria del proyecto entre sesiones. Máximo ~50 líneas: resume o elimina lo que ya no aporte.

## Estado actual

- v1 desplegada en Railway: catálogo con buscador y filtros, favoritos, mezclas y modo admin (sabores, marcas, categorías y tipos de tabaco).
- Sin sesión se ven la portada (`home`), Marcas y las mezclas públicas (`mixes.public`); el resto pide iniciar sesión (specs 006 y 007). Esas vistas no pueden asumir que hay usuario.
- Imágenes: volumen de Railway en `/app/storage/app/public` (sobreviven a los redeploys; plan Free, vigilar consumo; alternativa: alwaysdata). Resubidas en 2026-11-04/05. Sin foto: los 9 de Street Smoke (llevan el logo de la marca, decisión del usuario) y unos 5 más. Hay 2 Zero Gravity: 136 (Overdozz) y 324 (Al-Waha). Los nombres no bastan para asignar fotos (Blyat comparte sabores con Andalushisha): mirar la lata.
- URL con `https://` gracias a `trustProxies(at: '*')` en `bootstrap/app.php` (`a8a76c1`).
- Navegación rediseñada en `layouts/app.blade.php` (`f2cbddc`): logo en `public/images/`, barra superior, pestañas inferiores en móvil, fila de secciones en admin. Crear y editar mezcla usan `mixes/_flavor-picker.blade.php` (el `<select>` es el campo enviado). Favoritos filtra y ordena en PHP, no en SQL.
- En producción: specs 001-005 (003 `4767a53`: modal de detalle único `flavors/_detail-modal.blade.php`, `partials/_filter-dropdown.blade.php`, `partials/_favorite-toggle.blade.php`; 004 `bc7b510`: `Flavor::sortedBy`; 005 `1a065dc`: filtros del admin de sabores con `Flavor::filteredBy`).
- Spec 006 (`edaecbc`): `Controller::rememberPageBeforeLogin` + `redirect()->intended`. Pendientes: registrarse desde Marcas no muestra el aviso; un destino antiguo gana a la última página vista.
- Spec 007 (`81c6399`): pestañas "Mis mezclas"/"Públicas" (`$tab`), `Mix::publicListing`, `Mix::sortedForListing`. Spec 008 (`45886e6`, CON migración `add_source_mix_id_to_mixes_table`): `saveCopy` crea una copia privada y añade sus sabores a favoritos. Spec 009 (`44e201d`): mezclas con sabores públicos (`MixController::flavorGroups`).
- Spec 010 (`a0dc910`, CON migración `create_flavor_prices_table`): `flavor_prices`, `FlavorPrice::formatPrice`; los precios los pone el usuario desde el admin. Spec 011 (`867596e`, CON migración `create_flavor_ratings_table`): valoración 1-5, `FlavorRatingController` (JSON), "Mejor valorados" y `min_rating`. Desde el 2026-11-11 la ventana del sabor muestra solo la media ("★ 4,3"), sin el número de valoraciones (petición del usuario).
- Spec 012 (`08a5b23`, CON migración que MUEVE DATOS `move_tobacco_types_to_table`, confirmada en producción): `tobacco_types` y admin "Tipos de tabaco". Tests: `tobaccoType('rubio')` en `tests/TestCase.php`; `TobaccoTypeMigrationTest` solo migra hasta `add_rojo`.
- Spec 013 (`b19d8c9`, en producción): "Crear mezcla" lleva a `mixes.create?flavor=<id>`; `MixController::linkedFlavorRows` solo acepta el id entero de un sabor público.
- Spec 014 (`09201f1`, sin migraciones): tiradores en `#mix-total-bar` (solo si todo suma 100), empieza 50/50, mínimo 2 sabores (`StoreMixRequest` `min:2`); las mezclas antiguas de 1 sabor siguen valiendo y al editarlas se añade una 2.ª fila vacía. Pendientes: con un sabor al 1 % los tiradores quedan casi encimados; al editar una mezcla de 1 sabor dice "Suma 100%: la mezcla está completa." aunque falta el 2.º (no deja guardar).
- Spec 015 (ligera, sin migraciones, sin subir): "Precio (menor a mayor)/(mayor a menor)" en portada, admin de sabores y Favoritos. Solo cuenta el precio de 50 g; sin él, al final en los dos sentidos (no se ocultan); desempate nombre A-Z y luego id. SQL en `Flavor::scopeSortedBy` (subconsulta + `CASE WHEN IS NULL`); Favoritos en PHP (`FavoriteController::priceSortKey`, céntimos). La tarjeta sigue mostrando "desde X €" (mínimo de todos los formatos). En el admin, precio va delante de las opciones de ID (aceptado). "Limpiar" conserva el orden en portada y admin y lo quita en Favoritos (como antes). En local solo un sabor tiene precio de 50 g: el orden apenas se nota en el navegador.
- Pendientes menores. Spec 003: el foco no vuelve a la tarjeta al cerrar el modal; una categoría muy larga se sale a 375 px; tras quitar favoritos sin recargar, los desplegables ofrecen opciones vacías; tarjetas `role="button"` con botones dentro. Spec 004: en Postgres el orden de cadenas depende de la intercalación. Layout: ~12 px de scroll horizontal a 1024 px por `header.us-topbar`.
- Errores conocidos (admin, solo con datos manipulados): un array en un campo de texto da error de servidor al volver al formulario; un `is_public` no booleano no muestra su mensaje. Son los 13 tests omitidos.

## Aprendizajes y errores a evitar

- GD está desactivado en local: no usar `UploadedFile::fake()->image()` en los tests.
- Chrome DevTools: `resize_page` no baja de 500 px (para 375 px, `emulate`), y a veces sus clics no llegan: usar `element.click()`/`form.submit()` desde `evaluate_script`.
- Si `public/storage` apunta a una ruta inexistente, en Windows hay que `rmdir public\storage` y luego `php artisan storage:link` (`--force` no lo arregla).
- No dar por hecho el estado de producción a partir de Git: antes de una migración que cambie una restricción, consultarla en producción.
- `.row` directamente dentro de `main`, sin `.container` ni `mx-0`, provoca scroll horizontal en el móvil.
- Al actualizar un sabor desde el admin, `update()` pisa todos los campos: reenviar los actuales (sobre todo `is_public`).
- Un `image_url` relleno no garantiza que el archivo exista: compruébalo pidiendo la URL.
- Migraciones nuevas: probar `migrate:rollback --step=1` en local. En MySQL, un único que empieza por una columna con clave foránea sustituye a su índice y el `down()` debe devolverlo antes de borrarlo (spec 008).
- El reviewer no arranca el servidor: antes de validar, el usuario debe tener `php artisan serve` y MySQL de XAMPP en marcha.

## Decisiones (y por qué)

- Postgres en producción (lo configurado en Railway); MySQL en local (lo que trae XAMPP).
- Mezclas con cualquier sabor público, independientes de favoritos (spec 009, decisión del usuario el 2026-11-10).
- Los porcentajes de una mezcla suman exactamente 100: representan el total.
- `mixes.is_public`: para que un usuario pueda ocultar sus mezclas a los demás.
- Bootstrap por CDN, sin Tailwind: lo usa toda la interfaz y evita un paso de build.
- Búsqueda sin acentos con `replace` y `lower` en SQL, sin `ilike` ni `unaccent`: igual en todos los motores y sin extensiones.
- Desde el 2026-11-16, `CLAUDE.md`, `.claude/`, `.mcp.json`, `docs/`, `MEMORY.md` y todas las `specs/` se suben al repo (público): muestran el flujo con agentes para el portfolio. Nunca subir `.claude/settings.local.json` (en `.gitignore`) ni datos sensibles.
- Mezclas: un modal por mezcla pintado en el servidor (`mixes/_mix-items.blade.php`); Editar y Eliminar usan `stopPropagation`.
- Sabores (spec 003): un único modal de detalle para Inicio y Favoritos; el corazón usa `fetch` y, si falla, `form.submit()`. `.filters-panel` lleva `z-index: 30` por su `backdrop-filter`.
- Orden del catálogo (spec 004): por defecto "Añadidos recientemente"; lista blanca `Flavor::CATALOG_SORTS`; nulos al final con `CASE WHEN ... IS NULL`; desempate por `id`.
- Decidido no corregir por ahora: `%` y `_` como comodines en la búsqueda, y el orden de los sabores al editar una mezcla.

## Próximos pasos

- Hoja de ruta del 2026-11-10 completa (specs 003-012; punto 10 hecho a mano en producción el 2026-11-11). Cada funcionalidad nueva lleva su propia ronda de preguntas.
- Color de la barra de una mezcla por categoría (fuera de alcance por ahora): color fijo en código según el nombre de la categoría, gris para las desconocidas, tonos distintos si se repite. Falta la lista real de categorías y colores del usuario.
- Catálogo: el BOE (Comisionado para el Mercado de Tabacos) es la fuente fiable de sabores y PVP en España (skills `flavors-create` y `flavors-price`, con scripts del BOE en `.claude/skills/flavors-price/`). Al 2026-11-12: 10 sabores de Taboo (ids 405-414) creados ocultos, 5 sin datos en "Bebida"; Andalushisha con precios salvo Cujaté y Yo Girl (sin categoría); Blyat completa (6,50 / 22,50); Adalya completa (3,85 / 14,00 / kilo 65,00). Al Fakher: 22 de 31 con precio (9 dudosos vacíos). El precio va por marca, pero ante cualquier duda se deja vacío (decisiones del usuario). Última revisión del BOE: 2026-11-11.
- Lentitud en producción (medido el 2026-11-12): imágenes muy pesadas (el usuario las está reduciendo a webp) y `php artisan serve` atiende en cola; propuesto `PHP_CLI_SERVER_WORKERS=4` en Railway y `loading="lazy"` en `index.blade.php:206` (sin aplicar).
- Vigilar: un byte nulo en un filtro podría dar error en Postgres (sin confirmar). Borrar una mezcla no usa transacción (`MixController`); bastaría `$mix->delete()`.
