# Plan — Spec 002: Logo nuevo, fotos de Favoritos y mezclas en grande

Parte de la spec aprobada (`spec.md`). Sin rutas, migraciones, modelos, controladores ni Form Requests nuevos o modificados.

## 1. Archivos

### Nuevos (requieren aprobación)
| Archivo | Responsabilidad | RF |
|---|---|---|
| `resources/views/mixes/_mix-items.blade.php` | Parcial con la barra de porcentajes y la lista de sabores (foto, nombre, marca y porcentaje) de una mezcla. Se usa en la tarjeta y en la ventana, con un parámetro de tamaño (`card` o `modal`). Mantiene los 6 colores actuales según la posición. | RF-9, 13, 14 |

No se crean imágenes ni tests nuevos. Las tres imágenes del logo **sustituyen** a las actuales con el mismo nombre, y los tests se añaden a archivos que ya existen. La imagen original del usuario no se copia al proyecto: solo se usa para generar las tres copias.

### Modificados
| Archivo | Cambio | RF |
|---|---|---|
| `public/images/logo-urrushisha.png` | Se sustituye por el logo nuevo redimensionado a 176x151 (las mismas medidas que el actual). | RF-1, 4 |
| `public/images/logo-urrushisha-grande.png` | Se sustituye por el logo nuevo redimensionado a 400x342. | RF-2, 4 |
| `public/images/favicon-urrushisha.png` | Se sustituye por el logo nuevo redimensionado a 64x55. | RF-3 |
| `resources/views/favorites/index.blade.php` | Solo CSS de `.flavor-image`. En la T2 se quitó el `padding` y el fondo pasó a `#ffffff`; en la T4, `object-fit` pasa de `contain` a `cover`. `.flavor-image-wrapper` conserva su degradado, que solo se ve cuando no hay foto (el marcador con la inicial). | RF-5 a 8 |
| `resources/views/mixes/index.blade.php` | Usa el parcial. Miniaturas de 4rem en la tarjeta y de 6rem en la ventana, con el mismo fondo, margen y borde. Tarjeta pulsable con `role="button"`, `tabindex="0"`, `aria-label` y apertura con clic e Intro. Las acciones de la tarjeta hacen `stopPropagation`. Una ventana modal de Bootstrap por mezcla, oscura y translúcida, con Editar y Eliminar. | RF-9 a 19 |
| `tests/Feature/MixTest.php` | Tests de la ventana (ver sección 8). | RF-13, 15, 16, 19 |
| `tests/Feature/ErrorPagesTest.php` | La página 404 sin sesión muestra el logo de la barra. | RF-1 |
| `tests/Feature/AuthTest.php` | Las tres imágenes del logo existen con sus medidas. | RF-1 a 4 |

No se tocan:
- `public/images/logo.png`, que no se usa y no se borra.
- `layouts/app.blade.php`, `login.blade.php` y `register.blade.php`: siguen apuntando a los mismos nombres de archivo, con el mismo `width` y `height`.
- El catálogo (`index.blade.php`).
- `MixController`: `index` ya carga `items.flavor.brand` y solo las mezclas del usuario.

## 2. Rutas

Ninguna nueva ni modificada. La ventana usa las rutas que ya existen: `mixes.edit` (GET) y `mixes.destroy` (DELETE), con el middleware `auth` y la comprobación de propietario del controlador.

## 3. Base de datos

- Ninguna migración ni ningún cambio en los modelos.
- No afecta a los datos de producción.

Checklist de Postgres y migraciones:
- [x] `like`: no se añaden consultas.
- [x] SQL específico de MySQL: no hay.
- [x] Migraciones existentes: no se tocan.
- [x] Migración nueva compatible con los datos: no hay ninguna.
- [x] Valores fuera de un `enum`: no se guarda nada.

## 4. Lógica

Sin cambios en controladores ni en Form Requests. La ventana se pinta en el servidor con los mismos datos que ya recibe la vista (`$mixes`), así que no hay consultas en la vista (constitución, principio 3).

## 5. Interfaz

### Logo (RF-1 a 4)
- Mismos nombres de archivo y mismas medidas en píxeles que ahora: el HTML no cambia y el tamaño en pantalla tampoco (`.us-brand img` a 3.1rem de alto; login y registro a `width="200"`).
- El navbar sigue mostrando el texto "URRUSHISHA" a la derecha del logo.

### Favoritos (RF-5 a 8)
- Cambio del 2026-11-05 (T4): la foto usa `object-fit: cover`, igual que el catálogo (`object-position` por defecto, centrado). Llena todo el hueco 4:3 sin deformarse y recorta lo que sobra por los bordes, así que no quedan bandas (RF-5, 6).
- Se mantienen `position: absolute; inset: 0; width: 100%; height: 100%`, sin `padding`, como dejó la T2.
- Se mantiene el `background: #ffffff`, que solo se nota detrás de las zonas transparentes de un PNG. Con `cover` no hay bandas.
- Las esquinas de arriba quedan redondeadas porque `.flavor-card` ya tiene `overflow: hidden` y `border-radius`.
- El marcador con la inicial sigue igual: si no hay foto o no carga, `onerror` quita el `<img>` y se ve el degradado con la inicial.
- La ventana de detalle de sabor (`.flavor-modal-*`) no se toca.

### Mezclas: tarjeta (RF-9 a 12)
- `.mix-flavor-thumb` pasa de 2.9rem a 4rem (unos 64 px). Se mantienen el `#f5f3ff`, los 3 px de margen y el `outline` del color del tramo.
- `<article class="mix-card">` es pulsable:
  - Atributos: `role="button"`, `tabindex="0"`, `aria-label="Ver la mezcla <nombre>"` y `cursor: pointer`.
  - Se abre con un `onclick`.
  - Se abre con un `onkeydown` solo con Intro y solo si `event.target === this`, como en Favoritos.
  - Tiene un borde de color al pasar el ratón y con `:focus-visible`.
- En el pie de la tarjeta, el enlace Editar y el formulario Eliminar llevan `onclick="event.stopPropagation()"` (y `onkeydown` igual). Así hacen su acción sin abrir la ventana. Eliminar mantiene su `confirm`.

### Mezclas: ventana (RF-13 a 19)
- Una ventana modal de Bootstrap 5.3 por mezcla, con id `mixModal-{id}`, pintada después de la rejilla (fuera de la tarjeta, para que sus clics no suban a ella). Usa `modal-dialog-centered`, `modal-dialog-scrollable` y `modal-lg`.
- Aspecto: `.modal-content` con el mismo fondo oscuro y translúcido que `.mix-card`, más `backdrop-filter: blur`, `border: 1px solid var(--us-line)`, `border-radius: 22px` y texto blanco.
- Contenido:
  - Cabecera: nombre y "Pública" o "Privada", con un botón de cerrar `btn-close btn-close-white` con `aria-label="Cerrar"`.
  - Cuerpo: el parcial en tamaño `modal` (miniaturas de 6rem, unos 96 px, con `contain`), las notas si existen y la fecha con la misma condición que la tarjeta (`$mix->created_at ? ... : ''`).
  - Pie: Editar (enlace a `mixes.edit`) y el formulario Eliminar (DELETE a `mixes.destroy`), con el mismo `onsubmit="return confirm(...)"` que el listado.
- Comportamiento de Bootstrap, sin JavaScript propio:
  - El botón de cerrar, el clic fuera y Escape cierran la ventana (RF-17).
  - `modal-open` en `body` impide que el listado se desplace (RF-18).
  - Si se cancela el `confirm`, el formulario no se envía y la ventana sigue abierta.
  - Si se confirma, la petición DELETE redirige al listado con el mensaje de éxito actual (RF-16).
- JavaScript propio en `@push('scripts')`: solo una función `openMixModal(id)` que llama a `bootstrap.Modal.getOrCreateInstance(...).show()`.
- Solo se pintan ventanas para las mezclas de `$mixes`, que ya vienen filtradas por el usuario (RF-19).

### Móvil (RNF-1)
- Se usa `modal-dialog-scrollable` con un margen pequeño, para que la ventana quepa a 375 px con desplazamiento interior. No se usa `modal-fullscreen-sm-down`, para que en el móvil se siga viendo como una tarjeta.
- Los textos largos llevan `overflow-wrap: anywhere`, como ya hace `.mix-card`.
- El modal de Bootstrap (z-index 1055) queda por encima de la barra inferior de pestañas (1030) y de la superior (`sticky-top`, 1020).

## 6. Permisos

| Usuario | Qué puede hacer |
|---|---|
| Visitante sin sesión | Ve el logo nuevo en login, registro, 403 y 404, y el favicon. No llega a Favoritos ni a Mezclas: el middleware `auth` lo manda al login, como ahora. |
| Usuario registrado | Ve sus favoritos y sus mezclas. La ventana solo existe para sus mezclas. Editar y Eliminar siguen comprobando en el servidor que la mezcla es suya (403 si no lo es). |
| Admin | Lo mismo que el usuario registrado; tampoco puede tocar mezclas ajenas, como ahora. |

## 7. Decisiones técnicas

1. **Redimensionar el logo con PowerShell y `System.Drawing` (.NET, ya incluido en Windows), en una única ejecución desde el scratchpad.** Se generan las tres copias con transparencia y con interpolación `HighQualityBicubic`, con los nombres y medidas actuales. Solo se suben los PNG resultantes; el script no queda en el proyecto. No es una dependencia nueva ni un paso de build.
   - Descartado: usar el original (748x640, 311 KB) en los tres sitios con el tamaño fijado en CSS. Cada página descargaría 311 KB para pintar unos 58x50 px, y el favicon pesaría 311 KB.
   - Descartado: GD o Imagick, que están desactivados en local o exigirían instalar algo.
   - Si `System.Drawing` fallara, se para y se pregunta. No se sube el original sin aprobarlo.
2. **Mismos nombres de archivo.** No cambia ninguna vista y los tests actuales siguen valiendo.
   - Descartado: archivos nuevos con otro nombre o con `?v=2` en el favicon, para saltarse la caché. Obligaría a tocar el layout y los tests que comprueban la URL exacta del favicon (`AuthTest`, test del proxy https). La caché del favicon solo retrasa el cambio en navegadores que ya visitaron la web.
3. **Ventana modal de Bootstrap, una por mezcla, pintada en el servidor.**
   - Bootstrap ya está cargado y da gratis el cierre con Escape y con clic fuera, el bloqueo del desplazamiento y la accesibilidad del diálogo.
   - Blade escapa los textos y el formulario de Eliminar es un formulario normal.
   - Descartado: una sola ventana propia rellenada con JavaScript desde atributos `data-*`, como la de sabor. Habría que serializar la lista de sabores y construir el DOM en JS, con más código y más riesgo al escapar textos. El número de mezclas de un usuario es pequeño, así que repetir el HTML no pesa.
4. **Parcial `_mix-items`.** Evita duplicar la barra y la lista entre la tarjeta y la ventana, y garantiza los mismos colores en las dos (RF-13).
   - Descartado: copiar el bloque en la vista, porque los dos se desincronizarían.
5. **Favoritos: solo CSS de `.flavor-image`, con `object-fit: cover` (cambio del 2026-11-05).** Es lo mismo que usa el catálogo y elimina las bandas con cualquier fondo de foto. El degradado del contenedor solo se ve sin foto.
   - Descartado: `contain` sobre fondo blanco (lo que se implementó en la T2). Deja bandas blancas en las fotos con fondo de color, como el rojo.
   - Descartado: `contain` con un fondo tomado del color de la foto. Exigiría analizar la imagen, con GD desactivado o en JavaScript.
   - Descartado: tocar la estructura de la tarjeta o el catálogo, que queda fuera de alcance.

## 8. Estrategia de tests (`php artisan test`)

Los cambios de CSS (RF-5, 6, 8, 9, 14, 17, 18 y RNF) son visuales: no llevan test, y el reviewer los comprueba en el navegador. Los tests nuevos van en archivos existentes, con `RefreshDatabase` y factories.

| Test | Archivo | RF |
|---|---|---|
| `test_the_logo_images_keep_their_sizes`: `getimagesize` (PHP básico, no necesita GD) devuelve 176x151, 400x342 y 64x55. Es una salvaguarda que ya pasa antes del cambio: que la imagen sea la nueva lo comprueba el reviewer. | `AuthTest.php` | RF-1 a 4 |
| `test_the_404_page_shows_the_navbar_logo_without_a_session` | `ErrorPagesTest.php` | RF-1 |
| `test_the_list_has_a_window_for_each_own_mix_with_its_data`: id `mixModal-{id}`, nombre, notas, sabores con marca y porcentaje, y "Pública" o "Privada". | `MixTest.php` | RF-13 |
| `test_the_mix_window_has_edit_and_delete_actions`: enlace a `mixes.edit` y formulario a `mixes.destroy` con `confirm`. | `MixTest.php` | RF-15, 16 |
| `test_the_list_has_no_window_for_foreign_mixes`: no aparece `mixModal-{id}` de una mezcla ajena, aunque sea pública. | `MixTest.php` | RF-19 |
| `test_a_mix_without_flavors_shows_the_empty_text_in_its_window` | `MixTest.php` | RF-13 (caso límite) |
| `test_a_mix_without_created_at_shows_no_date`: fecha puesta a `null` con una consulta. | `MixTest.php` | RF-13 |
| Ya existen y deben seguir en verde: `test_login_and_registration_show_the_logo_and_every_page_has_its_icon`, el test del proxy https, `test_the_list_draws_the_composition_of_each_mix_with_its_brand`, `test_deleting_a_mix_...` y los de mezclas ajenas. | varios | RF-2, 3, 12, 16, 19 |

La tarjeta pulsable (RF-10 y 11) y el cierre de la ventana (RF-17) son comportamiento del navegador: los comprueba el reviewer.
