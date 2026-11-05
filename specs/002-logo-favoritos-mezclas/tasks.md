# Tareas — Spec 002: Logo nuevo, fotos de Favoritos y mezclas en grande

Plan: `plan.md`. Una tarea cada vez: tests primero, después el cambio, `php artisan test` en verde, marcar y parar.

- [x] **T1. Logo nuevo en la barra, login y registro, y favicon.** RF-1, RF-2, RF-3, RF-4
  - Archivos: `public/images/logo-urrushisha.png`, `public/images/logo-urrushisha-grande.png` y `public/images/favicon-urrushisha.png`. Se sustituyen; no son archivos nuevos.
  - Tests primero:
    - En `tests/Feature/AuthTest.php`, añadir `test_the_logo_images_keep_their_sizes` (comprueba las medidas con `getimagesize`).
    - En `tests/Feature/ErrorPagesTest.php`, añadir `test_the_404_page_shows_the_navbar_logo_without_a_session`.
  - Generar las tres copias desde `C:\Users\ivane\Downloads\LOGO-URRUSHISHA (1).png`:
    - Herramienta: PowerShell con `System.Drawing`, con el script en el scratchpad y no en el proyecto.
    - Formato: PNG con transparencia e interpolación `HighQualityBicubic`.
    - Medidas: 176x151, 400x342 y 64x55.
    - Si `System.Drawing` falla, parar y preguntar.
  - No tocar `public/images/logo.png` ni las vistas.
  - Hecho cuando:
    - Los tres PNG muestran el escudo nuevo con "URRUSHISHA" en mayúsculas, con fondo transparente y las medidas indicadas.
    - Los tests nuevos y los que ya había del logo y el favicon pasan.
    - `php artisan test` está en verde.
    - Comprobación rápida: la barra a 375 px muestra el logo nuevo junto al texto "URRUSHISHA".

- [x] **T2. Foto de Favoritos sobre el fondo blanco de la tarjeta.** RF-5, RF-6, RF-7, RF-8
  - Archivo: `resources/views/favorites/index.blade.php`, solo el CSS de `.flavor-image`: sin `padding` y con `background: #ffffff`.
  - Sin tests nuevos, porque es un cambio visual.
  - Hecho cuando:
    - En Favoritos, la foto se ve entera (`contain`), sin margen y sobre blanco igual que la tarjeta.
    - Sin foto, se ve la inicial sobre el degradado, como antes.
    - El catálogo y la ventana de detalle de sabor no cambian.
    - `php artisan test` está en verde.
    - Comprobación rápida a 375 px y en la consola.

- [x] **T3. Mezclas: fotos más grandes y ventana de la mezcla con Editar y Eliminar.** RF-9 a RF-19
  - Archivos:
    - `resources/views/mixes/_mix-items.blade.php` (nuevo): la barra y la lista de sabores, con tamaño `card` o `modal`.
    - `resources/views/mixes/index.blade.php`: tarjeta pulsable con clic e Intro; acciones con `stopPropagation`; una ventana modal de Bootstrap oscura por mezcla; miniaturas de 4rem y 6rem con el mismo fondo, margen y borde.
    - `tests/Feature/MixTest.php`.
  - Tests primero (en rojo), en `MixTest.php`:
    - `test_the_list_has_a_window_for_each_own_mix_with_its_data`
    - `test_the_mix_window_has_edit_and_delete_actions`
    - `test_the_list_has_no_window_for_foreign_mixes`
    - `test_a_mix_without_flavors_shows_the_empty_text_in_its_window`
    - `test_a_mix_without_created_at_shows_no_date`
  - Hecho cuando:
    - Los tests nuevos pasan y siguen en verde los de mezclas que ya había (composición, borrado y mezclas ajenas). `php artisan test` está en verde.
    - Comprobación rápida a 375 px:
      - la ventana se abre al pulsar una tarjeta y se cierra con Escape;
      - Editar y Eliminar de la tarjeta no la abren;
      - la consola no muestra errores.
