# CLAUDE.md — UrruShisha

Web para consultar sabores de cachimba con buscador y filtros, guardar sabores en favoritos, ver su información y crear mezclas con ellos. Tiene un modo admin para gestionar sabores, marcas y categorías.

## Stack y estructura

- Laravel 12, PHP 8.2+, vistas Blade.
- Interfaz con **Bootstrap 5.3 por CDN** (cargado en `resources/views/layouts/app.blade.php`, tema oscuro con `data-bs-theme="dark"`). No usar Tailwind: aunque está instalado junto a Vite, solo lo usa `welcome.blade.php`, que es la vista por defecto de Laravel y no forma parte de la app.
- Base de datos: MySQL de XAMPP en local, Postgres en producción (Railway).
- Despliegue: Railway construye desde GitHub. Comando de arranque:
  `php artisan storage:link || true && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080`

Lo que no es obvio:

- `app/Http/Controllers/Admin/`: CRUD de sabores, marcas y categorías del admin. Los controladores con el mismo nombre fuera de `Admin/` son la parte pública.
- `resources/views/index.blade.php`: el catálogo de sabores con el buscador (es la portada, ruta `home`).
- `resources/views/js/`: el JavaScript de login y registro, en formato Blade.
- `app/Http/Middleware/AdminMiddleware.php`: registrado como alias `admin` en `bootstrap/app.php`; comprueba `users.is_admin`.
- `app/Providers/AppServiceProvider.php`: crea el enlace `public/storage` si no existe.

Ignorar (restos de plantillas o del IDE, no se usan; no borrarlos sin preguntar):
`app/Models/Usuario.php`, `routes/api.php`, `schema.sql`, `nbproject/`, `urrushisha/`, `.github/`, `CHANGELOG.md`, `welcome.blade.php` y los métodos vacíos de `FlavorController`, `CategoryController` y `BrandController` (los de fuera de `Admin/`).

## Comandos

```bash
composer install
php artisan serve          # arrancar en local
php artisan migrate        # aplicar migraciones en local
php artisan test           # ejecutar los tests
php artisan db:seed --class=LocalTestUsersSeeder   # usuarios de prueba (solo en local)
```

No ejecutar `vendor/bin/pint` sobre todo el proyecto: reformatearía todos los archivos.

## Convenciones

- Interfaz y documentación en español; código y mensajes de commit en inglés.
- Código simple, nombres descriptivos y comentarios solo donde aporten.
- Diseño limpio y responsive con clases de Bootstrap; cualquier pantalla nueva debe verse bien en el móvil.
- Validación en Form Requests (`app/Http/Requests/`) con los mensajes de error en español.

## Reglas de dominio / trampas conocidas

- **Producción es Postgres, local es MySQL.** Todo lo que se escriba debe funcionar en Postgres. `like` distingue mayúsculas en Postgres (usar `ilike` o comparar en minúsculas) y no usar SQL específico de MySQL.
- **Cada deploy ejecuta `migrate --force`.** Una migración nueva se aplica en producción en cuanto se sube a GitHub. Nunca modificar migraciones ya existentes: crear una nueva.
- **Imágenes:** se guardan en el disco `public` (`storage/app/public/flavors`) y en `flavors.image_url` va la ruta relativa. El sistema de archivos del contenedor de Railway es efímero. No cambiar cómo se guardan sin preguntar.
- **Mezclas:** los porcentajes son enteros entre 1 y 100 y deben sumar exactamente 100. Una mezcla necesita al menos 2 sabores (spec 014); las antiguas de 1 sabor se conservan, pero al editarlas hay que añadir otro. Solo se pueden añadir sabores públicos del catálogo (al editar, los que ya estaban en la mezcla pueden quedarse aunque hayan dejado de ser públicos); no hace falta tenerlos en favoritos. Un sabor no puede repetirse en la misma mezcla. Guardar una mezcla pública ajena crea una copia privada y añade sus sabores a favoritos.
- **`mixes.is_public`** sirve para que un usuario pueda ocultar sus mezclas a los demás.
- **`mix_items.role`** no se usa. No usarlo ni eliminarlo.
- **Tipos de tabaco:** están en la tabla `tobacco_types` (los gestiona el admin en "Tipos de tabaco") y cada sabor apunta a su tipo con `flavors.tobacco_type_id` (desde la spec 012; la antigua columna `tobacco_type` con lista cerrada ya no existe). Un tipo que usan sabores no se puede borrar. En la interfaz se muestra `$flavor->tobacco_type_label` (el nombre del tipo); en las listas, cargar `tobaccoType` con `with()`.
- **`favorites`** no tiene columna `id` (clave primaria compuesta `user_id` + `flavor_id`). Borrar con consultas (`where(...)->delete()`), no con `$favorite->delete()`.
- **Admins:** se marcan a mano en la base de datos (`users.is_admin = true`). No hay pantalla para ello.

## Memoria

- Al empezar, lee `MEMORY.md` para conocer el estado del proyecto y las decisiones tomadas.
- Al terminar una tarea, actualízalo: estado actual, decisiones importantes (con su porqué) y errores a evitar. En el flujo SDD se actualiza solo en el cierre (skill sdd).
- Mantenlo breve (máximo ~50 líneas): resume o elimina lo que ya no aporte.
- Si algo se convierte en una regla permanente, propón moverlo a `CLAUDE.md` en lugar de dejarlo en la memoria.
- No guardes nunca datos sensibles (claves, tokens, datos personales).

## Forma de trabajar

- Haz solo lo que se pide: no añadas funcionalidades por tu cuenta.
- Cambios pequeños y enfocados; no reescribas lo que ya funciona.
- Al terminar, resume qué has cambiado y cualquier decisión que deba revisar.
- ✅ Siempre: respetar las reglas escritas, mantener los textos de la interfaz en español, actualizar `MEMORY.md` al terminar cada tarea.
- ⚠️ Pregunta antes: crear archivos nuevos (incluidas migraciones), cambiar el formato de los datos guardados, hacer commit o push (un push despliega en producción). En el flujo SDD, aprobar el plan cuenta como permiso para crear los archivos que lista.
- 🚫 Nunca: añadir dependencias, frameworks o un paso de build.

## Flujo de Git

- Commit solo cuando se pida o en el cierre del flujo SDD, con un mensaje en inglés que describa el cambio.
- Antes del commit: `git status` y `git diff` para comprobar que solo van los archivos del cambio (nunca `.env`).
- El push SIEMPRE requiere la confirmación explícita del usuario: despliega en Railway y ejecuta `migrate --force` en producción. Si el commit incluye migraciones, avisa antes de pedirla.

## Verificación

- Si el cambio toca PHP: `php artisan test` debe pasar.
- Si toca una pantalla: con `php artisan serve` en marcha, comprobar con Chrome DevTools que carga sin errores en consola y que se ve bien a 375 px. Para iniciar sesión, usar los usuarios de `LocalTestUsersSeeder`.
- Si toca consultas a la base de datos: revisar que sean compatibles con Postgres.
- Si el cambio necesitaría un test nuevo, proponerlo (crear el archivo requiere preguntar antes).