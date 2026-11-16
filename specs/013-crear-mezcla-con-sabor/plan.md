# Plan breve — 013: "Crear mezcla" desde un sabor, con el sabor ya elegido (modo ligero) — Estado: cerrado (2026-11-11)

- **Cambio**: hoy el botón "Crear mezcla" de la ventana de un sabor (Inicio y Favoritos) abre el formulario de mezcla vacío. Pasa a abrirlo con ese sabor ya elegido en la primera fila, para que el usuario solo añada el porcentaje y el resto de sabores. Lo pidió el usuario tras la spec 012.
- **Archivos**: `app/Http/Controllers/MixController.php` (`create`), `resources/views/flavors/_detail-modal.blade.php` (enlace), `tests/Feature/MixTest.php`. Ningún archivo nuevo; sin migraciones ni dependencias.
- **RF1** (enlace): "Crear mezcla" lleva a `mixes.create` con `?flavor=<id del sabor>`.
- **RF2** (formulario): si `flavor` es el id de un sabor público, la primera fila del formulario sale con ese sabor elegido y el porcentaje vacío (el buscador muestra su nombre). Si `flavor` no es un id válido, no existe, no es público o llega como array, se ignora y el formulario sale vacío como hoy (sin error). Si se vuelve al formulario tras un error de validación, manda lo que escribió el usuario (no se vuelve a poner el sabor del enlace).
- **RF3** (sin sesión): el botón lleva a la misma dirección (`/mixes/create?flavor=<id>`), que pide sesión: Laravel manda a iniciar sesión y, al entrar, abre el formulario con el sabor ya elegido (es el destino que guarda Laravel al pedir una página protegida; spec 006).
- **RF4**: a 375 px, el formulario con el sabor elegido se ve bien; consola sin errores.
- **Fuera de alcance**: elegir varios sabores desde el catálogo; poner un porcentaje por defecto (por ejemplo, 100); un botón "Crear mezcla" en la tarjeta (sigue solo en la ventana).
- **Decisiones**:
  - Sin sesión, el enlace va directo al formulario (y no a login): así, tras entrar, el usuario llega al formulario con el sabor, en lugar de volver al catálogo. Cambia lo que hacía la spec 006 con este botón (antes, tras entrar, volvía al catálogo).
  - El id se valida con `filter_var` antes de consultar (Postgres rechazaría un texto) y solo se admite un sabor público, la misma regla que al guardar (spec 009).
- **Tests existentes que cambian**: los de `CatalogTest` y `FlavorRatingTest`/`FavoriteTest` que comprueban el `href` exacto de "Crear mezcla" (con y sin sesión) pasan a esperar `mixes.create` con `?flavor=<id>`.
- [x] **T1**, RF1–RF4:
  - Primero, los tests en rojo en `MixTest`: con `?flavor=` de un sabor público, la primera fila sale con él elegido; con un id inexistente, no público, texto, cero o array, el formulario sale vacío y sin error; tras un error de validación manda lo escrito; sin sesión, `?flavor=` va a login y tras entrar vuelve al formulario con el sabor; el enlace de la ventana lleva `?flavor=` (con y sin sesión); los tests existentes del enlace, ajustados.
  - Después, `MixController::create` y el enlace de `_detail-modal.blade.php`.
  - Hecho cuando: `php artisan test` está en verde y, a 375 px, al pulsar "Crear mezcla" en un sabor el formulario se abre con ese sabor elegido, sin errores en consola.
