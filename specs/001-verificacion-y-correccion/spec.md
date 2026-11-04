# Spec 001 — Verificación de la aplicación y corrección de errores
Estado: aprobada

## Contexto y objetivo
El usuario pide "un test para comprobar que funciona todo correctamente y si no corrige errores". Hoy la aplicación está desplegada, pero no tiene tests reales: el único test de Feature es el de ejemplo y está en rojo.

Objetivo:
1. Dejar una batería de tests automáticos que cubra acceso, catálogo, marcas, favoritos, mezclas y administración.
2. Corregir los errores que esa batería y la revisión del código destapen, dentro del criterio de esta spec.
3. Añadir `rojo` a la lista cerrada de `tobacco_type`, por decisión expresa del usuario.
4. Hacer que el buscador ignore los acentos también en producción, por decisión expresa del usuario.

No se añade ninguna otra funcionalidad nueva. Las únicas pantallas nuevas son las de marcas (RF-13 y RF-14), cuyas rutas ya existen y hoy no funcionan, y las páginas de error 403 y 404 en español (RF-62).

## Usuarios
- **Visitante sin sesión**: solo accede a login y registro.
- **Usuario registrado**: catálogo, marcas, favoritos y sus propias mezclas.
- **Admin** (`users.is_admin = true`): lo anterior más la gestión de sabores, marcas y categorías.

## Historias de usuario
- HU-1. Como responsable del proyecto, quiero una batería de tests para saber qué funciona y detectar regresiones.
- HU-2. Como visitante, quiero registrarme e iniciar sesión, y ver qué ha fallado si me equivoco.
- HU-3. Como usuario registrado, quiero buscar y filtrar sabores sin preocuparme de mayúsculas ni acentos, y obtener los mismos resultados en producción que en local.
- HU-4. Como usuario registrado, quiero guardar favoritos y crear y editar mezclas válidas sin que nadie más pueda tocarlas.
- HU-5. Como admin, quiero gestionar sabores, marcas y categorías sin errores de servidor y sin perder datos.

## Definiciones
- **Lista de tipos de tabaco**: `rubio`, `negro`, `herbal`, `rojo`, `sin_nicotina`. Es una lista cerrada; el valor vacío sigue permitido.
- **Error de servidor**: respuesta con estado 500 o superior.
- **Mezcla válida**: tiene al menos un sabor, no repite sabores, y sus porcentajes son enteros de 1 a 100 que suman exactamente 100.
- **Sabor ya presente**: sabor guardado en la mezcla cuando empieza la edición en curso. Sigue siéndolo durante toda esa edición, aunque se quite de una fila o la edición se rechace.
- **Id válido**: número entero de 1 a 9223372036854775807 (el máximo de `bigint`). Cualquier otra cosa no lo es: texto, decimal, array o un número mayor.
- **Vocales acentuadas**: `á é í ó ú ü`, en mayúscula y minúscula. Otros signos (`è`, `ç`, `ö`) no están cubiertos.
- **Pantalla rota**: pantalla de la aplicación con error de servidor; o sin el contenido que debe mostrar; o con un error en la consola del navegador; o cuya acción principal falla sin indicar el motivo.

### Cómo se verifica cada RF
- **[T]**: test automático de Feature. No ejecuta JavaScript.
- **[R]**: revisión del código por el reviewer, con el checklist de Postgres y migraciones y con los criterios escritos en el propio RF. La validación debe indicarlo como "revisado, no ejecutado".
- **[N]**: comprobación en el navegador por el reviewer, solo dentro de los flujos de RF-67.
- **[U]**: comando que ejecuta el usuario en local, porque ni el implementer ni el reviewer pueden ejecutar migraciones ni seeders. Ver "Preparación del entorno de validación".

## Requisitos funcionales

### Acceso
- RF-1: CUANDO un visitante se registra con datos válidos, EL SISTEMA crea la cuenta, inicia su sesión y lo lleva al catálogo. [T]
- RF-2: SI una petición de registro incluye `is_admin`, ENTONCES EL SISTEMA crea la cuenta sin permisos de admin. [T]
- RF-3: SI el registro no es válido (campo vacío, email con formato incorrecto o ya registrado, contraseña de menos de 8 caracteres o que no coincide con su confirmación), ENTONCES EL SISTEMA no crea la cuenta y muestra el error en español en el formulario, conservando nombre y email. [T]
- RF-4: CUANDO un visitante inicia sesión con credenciales correctas, EL SISTEMA abre la sesión y lo lleva al catálogo. [T]
- RF-5: SI las credenciales son incorrectas, ENTONCES EL SISTEMA no abre la sesión y muestra un error en español. [T]
- RF-6: CUANDO un usuario cierra la sesión, EL SISTEMA la termina y lo lleva al login. [T]
- RF-7: MIENTRAS el usuario no ha iniciado sesión, EL SISTEMA redirige al login cualquier ruta que no sea de login o registro, incluido el catálogo. [T]
- RF-8: MIENTRAS el usuario tiene sesión, EL SISTEMA lo redirige al catálogo si intenta abrir login o registro. [T]
- RF-9: CUANDO se carga la pantalla de login, EL SISTEMA no incluye el script de registro que hoy provoca un error en la consola del navegador, y la consola queda sin errores. [T y N]

### Catálogo
- RF-10: MIENTRAS el usuario tiene sesión, EL SISTEMA muestra en el catálogo solo sabores con `is_public = true`, ordenados por nombre, con 12 por página, y conserva los filtros al cambiar de página. [T]
- RF-11: CUANDO el usuario filtra por marca, categoría, nombre, ingrediente o tipo de tabaco, EL SISTEMA devuelve solo los sabores que cumplen todos los filtros aplicados. Los filtros de texto admiten coincidencias parciales. [T]
- RF-12: Los filtros de nombre, ingrediente y tipo de tabaco ignoran mayúsculas y vocales acentuadas, y tratan `ñ` y `n` como iguales, tanto en lo que escribe el usuario como en lo guardado, en Postgres, MySQL y SQLite: `arandano`, `ARANDANO`, `Arándano` y `ÁRÁNDANO` encuentran `Arándano`, y `pina`, `PIÑA` y `piña` encuentran `Piña`. Lo cumple sin extensiones de base de datos, sin migración y sin cambiar datos guardados. [T para acentos y `ñ`; R para Postgres: el reviewer comprueba que la comparación no depende de que `like` ignore mayúsculas ni de la configuración regional de la base de datos]
- RF-12b: SI el filtro de marca o de categoría no es un id válido (`brand_id=abc`, `brand_id=99999999999999999999`, `brand_id[]=1`), ENTONCES EL SISTEMA muestra 0 resultados, igual que con un id inexistente, sin error de servidor. [T y R: el reviewer comprueba que ese valor no llega a la consulta]
- RF-12c: EL SISTEMA no sugiere en el filtro de tipo de tabaco ejemplos que no estén en la lista de tipos de tabaco (hoy sugiere "Virginia"). [T]
- RF-12d: CUANDO un sabor sin imagen tiene un nombre que empieza por una letra acentuada o `Ñ`, EL SISTEMA muestra esa inicial correctamente en el catálogo, en favoritos y en mezclas. [T]
- RF-12e: SI un filtro de texto llega como array (`name[]=x`, `ingredient[]=x`, `tobacco_type[]=x`), ENTONCES EL SISTEMA muestra 0 resultados y el campo vacío, sin error de servidor. [T]
- RF-61: CUANDO el usuario pulsa "Crear mezcla" en la ventana de detalle de un sabor, en el catálogo o en favoritos, EL SISTEMA lo lleva al formulario de creación, no al listado de mezclas. [T y N]
- RF-63: CUANDO el catálogo tiene más de una página, EL SISTEMA muestra la paginación en español ("Anterior", "Siguiente" y "Mostrando X a Y de Z resultados"), también en los textos para lectores de pantalla, sin ningún texto en inglés ("Showing", "Previous", "Next"). [T]

### Marcas (vista pública)
- RF-13: MIENTRAS el usuario tiene sesión, EL SISTEMA muestra una pantalla con todas las marcas ordenadas por nombre, y cada una enlaza con su detalle. [T]
- RF-14: MIENTRAS el usuario tiene sesión, CUANDO abre el detalle de una marca, EL SISTEMA muestra su nombre y solo sus sabores públicos. Si la marca no existe, responde 404. [T]
- RF-15: EL SISTEMA no añade enlaces hacia estas pantallas desde ninguna pantalla que ya exista; el único enlace nuevo es el del listado de marcas a su detalle (RF-13). Ambas usan las clases responsive de Bootstrap. [R]

### Favoritos
- RF-16: CUANDO el usuario pulsa favorito en un sabor que no tiene guardado, EL SISTEMA lo añade; si ya lo tiene, lo quita. En ambos casos muestra un mensaje en español. [T]
- RF-17: MIENTRAS el usuario tiene sesión, EL SISTEMA le muestra y le permite cambiar solo sus propios favoritos: quitar el suyo no afecta al de otro usuario. [T]
- RF-18: EL SISTEMA permite añadir, listar y quitar como favorito un sabor con `is_public = false`, sin que ese sabor aparezca por ello en el catálogo. [T]
- RF-19: SI el sabor no existe, ENTONCES EL SISTEMA responde 404. [T]

### Mezclas
- RF-20: CUANDO el usuario guarda una mezcla válida con sabores de sus favoritos, EL SISTEMA la crea con su nombre, notas, visibilidad, sabores y porcentajes. [T]
- RF-21: SI algún porcentaje no es un entero de 1 a 100 (0, 101, negativo o 50.5) o la suma es distinta de 100 (99 o 101), ENTONCES EL SISTEMA rechaza la mezcla sin guardar nada y muestra un error en español. [T]
- RF-22: SI una mezcla repite un sabor, ENTONCES EL SISTEMA la rechaza sin error de servidor, sin guardar nada y con un error en español. [T]
- RF-23: SI no hay ningún sabor, o cada sabor no tiene su porcentaje en la misma posición (distinto número de elementos, o `flavors[0]` con `ratios[5]`), ENTONCES EL SISTEMA rechaza la mezcla sin error de servidor y con un error en español. [T]
- RF-24: SI al crear una mezcla algún sabor no está en los favoritos del usuario, ENTONCES EL SISTEMA la rechaza con un error en español. [T]
- RF-25: EL SISTEMA no bloquea un sabor de la mezcla por tener `is_public = false` si está en favoritos o es un sabor ya presente. [T]
- RF-26: SI al editar una mezcla se añade un sabor que no está en favoritos y no es un sabor ya presente, ENTONCES EL SISTEMA rechaza el cambio con un error en español. [T]
- RF-27: CUANDO se edita una mezcla con un sabor ya presente que ya no está en favoritos, EL SISTEMA lo muestra seleccionado en su fila, permite guardarlo y lo ofrece también en las filas nuevas, para que se pueda quitar y volver a añadir en la misma edición. [T y N]
- RF-28: SI se rechaza la edición de una mezcla, ENTONCES EL SISTEMA deja guardados la mezcla, sus sabores y sus porcentajes tal como estaban. [T]
- RF-29: EL SISTEMA guarda cada mezcla y sus sabores como una sola operación: si falla una parte, al crear no queda ninguna mezcla y al editar queda la versión anterior completa. [T, forzando el fallo en el segundo sabor]
- RF-30: CUANDO se abre la edición de una mezcla, EL SISTEMA muestra los porcentajes como enteros, y guardar sin tocarlos funciona. El listado también los muestra como enteros (`50%`, no `50.00%`). [T y N; este fallo no aparece con la base de datos de los tests]
- RF-31: SI se rechaza la creación o la edición de una mezcla, ENTONCES EL SISTEMA muestra los errores en el formulario y conserva lo que escribió el usuario, no lo guardado: nombre, notas, visibilidad, filas de sabores y porcentajes. [T y N]
- RF-32: MIENTRAS el usuario tiene sesión, EL SISTEMA le lista solo sus propias mezclas, aunque las de otros usuarios sean públicas. [T]
- RF-33: SI un usuario intenta abrir, editar o eliminar la mezcla de otro, sea pública o privada, ENTONCES EL SISTEMA responde 403 sin modificar nada. [T]
- RF-34: CUANDO el usuario elimina una mezcla propia, EL SISTEMA borra la mezcla y sus sabores asociados, y no toca los sabores del catálogo ni los favoritos. [T]
- RF-58: SI el nombre de la mezcla está vacío o supera 180 caracteres, ENTONCES EL SISTEMA la rechaza al crear y al editar, sin error de servidor y con un error en español. [T]
- RF-59: EL SISTEMA valida la creación y la edición de mezclas en Form Requests, no en el controlador, con las mismas reglas y mensajes en ambos casos (principio 3 de la constitución). [R]

### Administración: acceso
- RF-35: MIENTRAS el usuario tiene sesión y `is_admin` es falso, EL SISTEMA responde 403 en todas las rutas de administración y en todos sus métodos, sin modificar nada. [T]
- RF-36: MIENTRAS el usuario tiene `is_admin` verdadero, EL SISTEMA le permite abrir todas las pantallas de administración. [T]
- RF-37: SI se pide la página de detalle de una marca, categoría o sabor dentro de administración (no existe), ENTONCES EL SISTEMA responde 404, no un error de servidor. [T]

### Administración: marcas y categorías
- RF-38: CUANDO el admin crea, edita o elimina una marca o categoría con datos válidos, EL SISTEMA aplica el cambio y muestra un mensaje en español acorde a la acción (al editar dice "actualizada", no "creada"). [T]
- RF-39: CUANDO el admin guarda una marca o categoría sin cambiar su nombre, EL SISTEMA lo acepta. [T]
- RF-40: SI el nombre está vacío, ya lo usa otro registro o supera el límite (marca: 120 caracteres; categoría: 80), ENTONCES EL SISTEMA lo rechaza con un error en español y sin error de servidor. [T]
- RF-41: SI el admin intenta eliminar una marca con sabores, ENTONCES EL SISTEMA no borra nada, no da error de servidor y muestra en la pantalla un mensaje en español con el motivo. [T]
- RF-42: CUANDO el admin elimina una categoría con sabores, EL SISTEMA conserva los sabores sin categoría, y estos se siguen mostrando en el catálogo. [T]

### Administración: sabores
- RF-43: CUANDO el admin crea, edita o elimina un sabor con datos válidos, EL SISTEMA aplica el cambio y muestra un mensaje en español acorde a la acción. [T]
- RF-44: CUANDO el admin ordena el listado por id, nombre o marca, en cualquier dirección, EL SISTEMA lo muestra en ese orden. Un criterio no permitido se sustituye por el orden por defecto. [T]
- RF-45: SI `tobacco_type` tiene un valor que no está en la lista de tipos de tabaco, ENTONCES EL SISTEMA lo rechaza sin error de servidor, con un error en español que indica los valores permitidos. Los 5 valores de la lista y el valor vacío se aceptan. [T]
- RF-46: SI faltan marca, categoría o nombre, la marca o categoría no existen, el nombre supera 180 caracteres o los ingredientes superan 500, ENTONCES EL SISTEMA lo rechaza con un error en español y sin error de servidor. [T]
- RF-47: SI el archivo subido no es una imagen jpg, jpeg, png o webp según su contenido real (también un archivo de texto renombrado a `.jpg`), o supera 2048 KB, ENTONCES EL SISTEMA lo rechaza con un error en español y no guarda el archivo. Un archivo de exactamente 2048 KB se acepta. [T]
- RF-48: CUANDO el admin sube una imagen válida, EL SISTEMA la guarda como hasta ahora y en `flavors.image_url` queda la ruta relativa. Si edita sin subir otra, conserva la anterior. [T]
- RF-49: SI el admin intenta eliminar un sabor usado en alguna mezcla, ENTONCES EL SISTEMA no borra nada, ni tampoco los favoritos de ese sabor; no da error de servidor y muestra en la pantalla un mensaje en español con el motivo. [T]
- RF-64: SI PHP rechaza la subida porque el archivo supera `upload_max_filesize` sin superar `post_max_size`, ENTONCES EL SISTEMA no guarda el sabor y muestra un error de validación en español. [T]
- RF-68: SI se rechaza la edición de un sabor guardado como público y la casilla pública se envió desmarcada, ENTONCES EL SISTEMA vuelve a mostrar el formulario con la casilla desmarcada. Al abrir el formulario sin errores, la casilla sigue mostrando el valor guardado. [T]

### Lista de `tobacco_type` en la base de datos
- RF-50: CUANDO se aplican todas las migraciones desde cero, EL SISTEMA acepta `rojo` en `tobacco_type` a nivel de base de datos y sigue rechazando valores fuera de la lista. [T]
- RF-51: CUANDO la migración nueva se aplica sobre una base de datos con la lista antigua de 4 valores y con filas en sabores, favoritos y mezclas, EL SISTEMA amplía la lista a 5 valores sin modificar ni perder ninguna fila de sabores, favoritos, mezclas ni sabores de mezcla, y conserva sus claves foráneas e índices. Funciona en Postgres, MySQL y SQLite.
  - [T] en SQLite, con filas en todas esas tablas y fuera de la transacción del test, comprobando filas antes y después y que las claves foráneas siguen activas.
  - [U] en el MySQL local: `php artisan migrate` termina sin error y los datos locales siguen disponibles.
  - [R] en Postgres, con estos criterios:
    - La migración obtiene la restricción CHECK real de la columna consultando el catálogo de Postgres, sin dar por supuesto su nombre.
    - Elimina todas las que afectan a `tobacco_type` y crea una con los 5 valores, de forma que no pueda quedar activa la lista antigua.
    - Ambos pasos se hacen en una sola transacción.
    - No ejecuta `UPDATE` ni `DELETE` y no cambia el tipo de la columna.
- RF-52: SI se revierte la migración nueva sin sabores con `rojo`, ENTONCES EL SISTEMA vuelve a la lista de 4 valores sin alterar ninguna fila de sabores, favoritos ni mezclas. SI hay sabores con `rojo`, ENTONCES la reversión falla con el mensaje en español "No se puede revertir: hay N sabores con tipo de tabaco rojo", donde N es el número real, y deja la lista y los datos intactos. [T en SQLite y R para Postgres, con los mismos criterios de RF-51]
- RF-53: EL SISTEMA añade el valor mediante una migración nueva. La migración original de sabores queda igual que en Git y no entra en el commit. [R con `git diff` y `git status`]

### Mensajes, textos y robustez
- RF-54: EL SISTEMA muestra un mensaje propio en español para cada regla de la tabla siguiente, sin texto en inglés ni claves como `validation.required`. [T, un caso por regla]

| Formulario | Campo | Reglas que deben tener mensaje en español |
|---|---|---|
| Login | email | obligatorio, formato |
| Login | contraseña | obligatorio, texto |
| Registro | nombre | obligatorio, texto, máximo 255 |
| Registro | email | obligatorio, texto, formato, máximo 255, ya registrado |
| Registro | contraseña | obligatorio, texto, mínimo 8, confirmación |
| Mezcla | nombre | obligatorio, texto, máximo 180 |
| Mezcla | notas | texto |
| Mezcla | sabores | obligatorio, lista, mínimo 1, repetido, posición sin porcentaje, no está en favoritos |
| Mezcla | cada sabor | obligatorio, id válido, existe |
| Mezcla | porcentajes | obligatorio, lista, mínimo 1, suma distinta de 100 |
| Mezcla | cada porcentaje | obligatorio, entero, mínimo 1, máximo 100 |
| Marca | nombre | obligatorio, texto, máximo 120, ya existe |
| Categoría | nombre | obligatorio, texto, máximo 80, ya existe |
| Sabor | marca, categoría | obligatorio, id válido, existe |
| Sabor | nombre | obligatorio, texto, máximo 180 |
| Sabor | descripción | texto |
| Sabor | tipo de tabaco | fuera de la lista |
| Sabor | ingredientes | texto, máximo 500 |
| Sabor | imagen | no es imagen, formato, máximo 2048 KB, subida fallida |
| Sabor | público | sí/no |

- RF-55: SI un identificador en la URL no es un id válido (`/mixes/abc/edit`, `/mixes/99999999999999999999/edit`), ENTONCES EL SISTEMA responde 404. SI lo es en un formulario, ENTONCES responde con un error de validación. En ningún caso da error de servidor. Los filtros del catálogo se rigen por RF-12b. [T para el comportamiento visible; R para Postgres: el valor no llega a la consulta]
- RF-60: EL SISTEMA muestra en español los textos de RF-54, RF-62 y RF-63 con cualquier valor de `APP_LOCALE`, sin depender de variables de entorno de Railway. [T con el idioma de la aplicación en `en`]
- RF-62: SI se responde 403 o 404, ENTONCES EL SISTEMA muestra una página en español con la aplicación, tenga el usuario sesión o no: "No tienes permiso para acceder a esta página" y "Página no encontrada", sin "Forbidden" ni "Not Found". No se añaden otras páginas de error. [T]

### Usuarios y datos de prueba en local
- RF-56: CUANDO se ejecuta `php artisan db:seed --class=LocalTestUsersSeeder` en local, EL SISTEMA crea o actualiza un usuario admin y un usuario normal. [T con entorno `local` simulado, y U]
- RF-57: SI ese comando se ejecuta fuera del entorno local, ENTONCES EL SISTEMA no crea ningún usuario. [T]
- RF-65: CUANDO se ejecuta en local el comando de datos de prueba, EL SISTEMA crea o actualiza, sin borrar nada y sin duplicar al repetirse:
  - una marca y una categoría;
  - más de 12 sabores públicos, incluidos uno con ingrediente `Arándano`, uno con nombre que empieza por letra acentuada y uno de tipo `rojo`;
  - un sabor no público;
  - favoritos para el usuario normal;
  - una mezcla del usuario normal al 50/50 con un sabor que está en favoritos y otro que no.

  Requiere haber ejecutado antes el comando de RF-56. [T con entorno `local` simulado, y U]
- RF-66: SI el comando de datos de prueba se ejecuta fuera del entorno local, ENTONCES EL SISTEMA no crea nada. [T]

### Flujos en el navegador
- RF-67: EL SISTEMA completa estos flujos en el navegador, con la consola sin errores y sin romperse a 375 px: [N]
  1. **Login**: credenciales incorrectas y correctas.
  2. **Catálogo**: abrir y cerrar la ventana de detalle con sus datos y su enlace "Crear mezcla"; mostrar los filtros en móvil y aplicarlos, incluida una búsqueda sin acento; cambiar de página.
  3. **Favoritos**: añadir y quitar desde el catálogo; quitar desde la pantalla de favoritos.
  4. **Crear mezcla**: añadir y quitar filas; enviar una suma distinta de 100, ver el error y conservar las filas; guardar una válida.
  5. **Editar mezcla**: porcentajes enteros; sabor ya presente fuera de favoritos seleccionado; quitarlo y volver a añadirlo en una fila nueva; guardar.

## Requisitos no funcionales
- RNF-1: `php artisan test` termina en verde: 0 tests fallidos y exactamente los 13 tests omitidos que enumera "Excepciones expresas". Cualquier otro test omitido cuenta como fallo. [T, la ejecuta el reviewer]
- RNF-2: Los tests no dependen de datos ya existentes en la base de datos ni necesitan red. [R]
- RNF-3: Ninguna dependencia nueva, framework ni paso de build. GD no se activa. [R: `composer.json` y `package.json` sin cambios en `git diff`]
- RNF-4: Las migraciones existentes no se modifican. La única migración nueva es la de `tobacco_type`. [R con `git status` y `git diff`]
- RNF-5: Todo lo que se escriba funciona en Postgres y en MySQL. [R]
- RNF-6: Interfaz y documentación en español; código en inglés. [R, y T para los textos de RF-54, RF-62 y RF-63]
- RNF-7: Los flujos de RF-67 se ven bien a 375 px. [N] El resto de pantallas nuevas o modificadas, incluidas marcas, páginas de error y administración, solo reciben la comprobación rápida del implementer cuando el servidor está en marcha y la revisión de sus clases responsive. [R]
- RNF-8: **Límite conocido**: los tests corren en SQLite en memoria y nada se ejecuta contra Postgres. Que estén en verde no demuestra que todo funcione en producción. Los RF con [R] solo se dan por cumplidos tras esa revisión, y la validación los indica como "revisado, no ejecutado". [R]

## Excepciones expresas decididas por el usuario
- **Test de ejemplo**: se mantiene su archivo y su aserción pasa a esperar la redirección al login (RF-7). Es la única excepción a "un test no se debilita": la aserción antigua describe un comportamiento que la aplicación no tiene.
- **Reversión de la migración original**: antes de la primera tarea, el implementer revierte a la versión de Git el archivo `database/migrations/2026_03_05_175025_create_flavors_table.php`, con autorización del usuario. Es la única excepción a "nunca modifiques una migración existente".
  - Se revierte solo ese archivo.
  - Justo antes de hacerlo, el implementer vuelve a comprobar con `git diff` que el único cambio es añadir `rojo`.
  - Si hay cualquier otro cambio, no revierte, para y avisa.
  - El `git diff` del reviewer ya confirmó que ese es el único cambio y que HEAD tiene los 4 valores.
- **Verificación en el navegador**: se limita a los flujos de RF-67. Las pantallas de administración se cubren con tests. Esto acota, solo para esta spec, lo que dicen CLAUDE.md y la definición del reviewer sobre comprobar todas las pantallas e interfaces.
- **Formularios de administración con datos manipulados (errores conocidos, sin corregir)**: "ningún error de servidor por datos no válidos" **no se cumple** en estos formularios de administración. Los errores siguen en la aplicación y figuran en "Temas pendientes".
  - **Error A, array en un campo de texto**: si `name`, `description`, `ingredients_text` o `tobacco_type` llegan como array, la validación rechaza la petición, pero al volver al formulario la vista da error de servidor. Afecta a crear y editar marcas, categorías y sabores.
  - **Error B, `is_public` no booleano**: la validación lo rechaza, pero `admin/flavors/create` y `admin/flavors/edit` no muestran el mensaje.
  - **Qué queda sin cumplir, y solo en esos casos:**
    - RF-40 (marca y categoría): "sin error de servidor" con `name` como array.
    - RF-45: "sin error de servidor" con `tobacco_type` como array.
    - RF-46: "sin error de servidor" con `name`, `description` o `ingredients_text` como array.
    - RF-54: que el mensaje se **muestre** en el formulario para esos arrays y para "público: sí/no".
    - RF-55: no queda sin cumplir ningún caso de identificadores. `brand_id` y `category_id` siguen sujetos a RF-55. "En ningún caso da error de servidor" no se extiende a los campos de texto del Error A.
  - **Qué sigue siendo obligatorio en esos mismos casos:** la petición se rechaza, no se guarda ni modifica nada, y la sesión contiene el mensaje en español de RF-54. Cualquier otro caso de esos RF se cumple sin excepción. La excepción no se aplica a login, registro, mezclas ni catálogo.
  - **Los 13 tests omitidos**: se conservan con sus aserciones sin cambios, marcados con `markTestSkipped` y el motivo. La omisión se aplica solo a estos casos; los demás casos del mismo método se siguen ejecutando.

    | Archivo | Método | Casos | Tests |
    |---|---|---|---|
    | `AdminBrandCategoryTest` | `test_an_array_as_name_does_not_break_the_form_when_creating` | "marca", "categoría" | 2 |
    | `AdminBrandCategoryTest` | `test_an_array_as_name_does_not_break_the_form_when_updating` | "marca", "categoría" | 2 |
    | `AdminFlavorTest` | `test_an_array_as_tobacco_type_does_not_break_the_forms` | (sin casos) | 1 |
    | `AdminFlavorTest` | `test_the_create_form_shows_the_error_without_a_server_error` | "nombre como array", "descripción como array", "ingredientes como array", "público no booleano" | 4 |
    | `AdminFlavorTest` | `test_the_edit_form_shows_the_error_without_a_server_error` | "nombre como array", "descripción como array", "ingredientes como array", "público no booleano" | 4 |

  - **Cómo lo valida el reviewer**: da por cumplidos esos RF si la suite omite exactamente estos 13 casos, y si los tests que comprueban el rechazo y el mensaje en la sesión pasan. Lo indica en la validación como "excepción expresa, error conocido".
- **No admin con un id válido inexistente en administración (excepción a RF-35)**: `/admin/brands/999999/edit` y rutas equivalentes responden 404 en vez de 403, porque el enlace de modelo se ejecuta antes que el middleware `admin`. No se modifica nada ni se muestra ningún dato. Con un registro que existe, RF-35 se cumple sin excepción. No se corrige, y figura en "Temas pendientes".

## Preparación del entorno de validación
El implementer y el reviewer no pueden ejecutar migraciones ni seeders, y el reviewer no arranca el servidor. Por eso, al terminar la última tarea y antes de la validación, el coordinador pide al usuario que ejecute en local, en este orden:
1. `php artisan migrate`
2. `php artisan db:seed --class=LocalTestUsersSeeder`
3. El comando de datos de prueba de RF-65.
4. `php artisan serve`

El usuario informa del resultado, que sirve como evidencia de los [U]. Solo afecta a la base de datos MySQL local. Como RF-65 crea un sabor `rojo`, si la migración no se hubiera aplicado el paso 3 fallaría en MySQL y el sabor no aparecería en el catálogo. El reviewer usa esos usuarios y datos; si faltan o el servidor no responde, para y avisa.

## Cómo se escriben los tests
La regla "tests primero, en rojo" se aplica así:
- **Corrección** (debe verse en rojo antes de corregir): RF-3, 9, 12 (acentos y `ñ`), 12c, 12d, 12e, 13, 14, 22, 23, 24, 26, 27, 28, 29, 31, 37, 38, 39, 40, 41, 45, 46, 47, 49, 50, 51, 52, 54, 56, 57, 58, 60, 61, 62, 63, 64, 65, 66, 68.
- **Caracterización** (comportamiento que ya funciona, nace en verde): el resto de RF con [T]. No se exige rojo. Cada test comprueba el resultado concreto y, cuando aplique, también el caso contrario (por ejemplo, el dueño accede y el otro usuario recibe 403). No basta con comprobar el estado HTTP.
- **No reproducible en SQLite** (RF-12b, RF-30 y la parte de Postgres de RF-12, RF-51, RF-52 y RF-55): el fallo no aparece con la base de datos de los tests, así que no se exige rojo. La corrección se demuestra con su [R] o [N].
- SI un test de corrección nace en verde, el implementer no lo fuerza a fallar: comprueba que el test sea correcto, lo indica en su respuesta y sigue. Esto no bloquea la tarea.
- SI un test de caracterización falla, es un error destapado: se aplica la sección siguiente.

## Tratamiento de los errores que se destapen
| Tipo de error | Qué se hace |
|---|---|
| Incumple un RF de esta spec | Se corrige dentro de la tarea, salvo lo que recoge "Excepciones expresas". |
| No tiene RF, pero da error de servidor, deja una pantalla rota o incumple una regla escrita (CLAUDE.md o constitución), y no entra en la lista de consulta | Primero se añade un RF a la spec (principio 2) y luego se corrige. |
| No tiene RF y no da error de servidor, ni deja una pantalla rota, ni incumple una regla (un texto o un enlace mejorable) | No se corrige. Se anota y se presenta al usuario, que decide. |
| Entra en la lista de consulta | Se para y se pregunta antes de tocar nada. |

**Lista de consulta**: cualquier migración nueva distinta de la de `tobacco_type`; cambiar el formato de los datos guardados o normalizar datos existentes; cambiar permisos o quién puede ver qué; añadir funcionalidad o cambiar una regla de dominio; cambiar cómo se guardan las imágenes; activar una extensión de PHP o de base de datos; borrar archivos de la lista de restos de CLAUDE.md.

**Según el momento:**
- Si el error aparece antes de aprobar el plan, el RF nuevo se añade a la spec y el plan ya lo incluye.
- Si aparece con el plan aprobado, el implementer para y lo explica. El coordinador actualiza la spec, el plan y las tareas, por ese orden, y el usuario aprueba el cambio antes de seguir.

Un test no se debilita ni se elimina para que pase, salvo la excepción expresa del test de ejemplo. Tampoco se omite, salvo los 13 tests de "Excepciones expresas". Si un test contradice un comportamiento que parece intencionado, se consulta.

## Casos límite
- Mezcla de un solo sabor al 100 %: es válida.
- Editar una mezcla cambiando un sabor ya presente que no está en favoritos por otro de favoritos: es válido. Una vez guardada, el sabor retirado deja de ser ya presente.
- Sabor ya presente que no está en favoritos y además tiene `is_public = false`: puede quedarse.
- Quitar de favoritos un sabor usado en una mezcla: la mezcla no cambia.
- Sabor favorito que el admin oculta después: sigue en favoritos, se puede quitar y no aparece en el catálogo.
- Eliminar un sabor no usado en mezclas: sus favoritos se borran con él. Su archivo de imagen no se borra, igual que hoy y que al sustituir una imagen.
- Sabor sin categoría (tras RF-42): al editarlo, el admin debe elegir una categoría, porque el formulario la exige.
- Filtro de tipo de tabaco con un texto fuera de la lista: 0 resultados, sin error.
- Filtros de marca o categoría vacíos: no filtran.
- `is_public` sin marcar en una mezcla o un sabor: se guarda como no público.
- Base de datos que ya acepta `rojo` (por ejemplo, un MySQL local creado con el archivo modificado): la migración nueva se aplica igual, sin error.
- Página 404 de una URL que no corresponde a ninguna ruta: se ve en español aunque no se detecte la sesión del usuario.
- Texto guardado con acentos en forma descompuesta (letra más signo combinado): no está cubierto por RF-12.

## Fuera de alcance
- Consultar el catálogo sin sesión (previsto en la memoria del proyecto, pero es funcionalidad nueva).
- Listado de mezclas públicas de otros usuarios.
- Pérdida de imágenes en cada deploy y su almacenamiento.
- Borrar archivos de imagen al sustituirlas o al eliminar un sabor.
- Peticiones que superan `post_max_size`: mantienen la respuesta 413 actual de Laravel.
- Páginas de error distintas de 403 y 404.
- Textos en inglés de la interfaz no incluidos en RF-54, RF-62 y RF-63, como el botón "Logout" o el título "Login".
- Ignorar signos distintos de las vocales acentuadas y la `ñ`.
- Límite de intentos de login.
- `mix_items.role`.
- Pantalla para marcar admins.
- Los restos que lista CLAUDE.md.
- Tests automáticos de navegador y comprobación en el navegador de administración y marcas.
- Ejecutar los tests contra Postgres.

## Temas pendientes de decisión del usuario (no se implementan en esta spec)
- **Email con mayúsculas en Postgres**: `Juan@x.com` y `juan@x.com` serían cuentas distintas en producción, y no pasa lo mismo en MySQL. Corregirlo implica normalizar datos guardados.
- **Vista parcial de navegación que no se usa**: ninguna vista la incluye y parece de otro proyecto (enlaces a reservas y tokens). No está en la lista de restos de CLAUDE.md. No se toca.
- **Array en campos de texto de los formularios de administración**: un array en `name`, `description`, `ingredients_text` o `tobacco_type` provoca un error de servidor al volver al formulario de crear o editar marcas, categorías y sabores. Tiene 11 tests omitidos a la espera de la corrección.
- **Error de `is_public` no visible**: los formularios de crear y editar sabor no muestran el mensaje si `is_public` no es booleano. Tiene 2 tests omitidos a la espera de la corrección.
- **404 en vez de 403 para quien no es admin**: con un id válido inexistente en una ruta de administración, responde 404, así que quien no es admin puede distinguir qué ids existen. No expone ni modifica datos.

## Criterios de finalización
- Todos los RF están cubiertos según sus marcas [T], [R], [N] y [U].
- `php artisan test` está en verde según RNF-1 (0 fallidos y exactamente los 13 omitidos de "Excepciones expresas"), y la validación indica cada RF [R] como "revisado, no ejecutado".
- El usuario ha informado del resultado de los comandos [U].
- La migración original de sabores no aparece en `git status`. Antes del commit, `git status` y `git diff` lo confirman.
- Antes de pedir el push, se avisa de que incluye una migración que se ejecutará en producción con `migrate --force`; de que su parte de Postgres no se ha ejecutado en ningún test; y de que, si falla, la aplicación no arranca.
- Tras el push, se pide al usuario que compruebe en producción que puede guardar un sabor de tipo `rojo` y que la búsqueda sin acentos encuentra uno con acentos.
- En el cierre se propone al usuario actualizar CLAUDE.md, que quedará desactualizado: dice que `tobacco_type` tiene 4 valores. Esta spec no lo edita.

## Dudas abiertas
Ninguna.

## Notas para el plan (hechos comprobados; no son requisitos)
Esta sección incluye detalles técnicos porque se pidió dejarlos indicados para el plan. No amplía el alcance.

**Buscador sin acentos (RF-12):**
- No hace falta `CREATE EXTENSION unaccent` (permisos sin comprobar en Railway, y no existe en MySQL ni SQLite), ni migración, ni una columna normalizada (que sí exigiría migración y rellenar datos).
- `replace()` anidado y `lower()` existen en los tres motores: se sustituye cada vocal acentuada y la `ñ`, en mayúscula y minúscula, en la columna, y se normaliza igual en PHP lo que escribe el usuario. `translate()` no sirve porque no existe en MySQL.
- Sustituir también las mayúsculas acentuadas y la `Ñ` es necesario: `lower()` de SQLite solo trata ASCII, y el de Postgres depende de la configuración regional.
- `ilike` rompe MySQL y SQLite, y `whereLike` de Laravel no ignora acentos en Postgres ni en SQLite.
- Los filtros con `%x%` ya recorren toda la tabla, así que no hay un índice que se pierda.
- Con esta solución, SQLite ejecuta la misma lógica de acentos, y su test sí empieza en rojo. Lo que SQLite no demuestra es que `lower()` sea imprescindible, porque su `like` ya ignora mayúsculas ASCII: de ahí el [R].

**Migración de `tobacco_type`** (leído en las gramáticas de Laravel de `vendor/`, sin ejecutar):
- **Postgres**: `enum` se crea como `varchar(255) check ("tobacco_type" in (...))`, es decir, una restricción CHECK. `->change()` generaría `alter column ... type varchar(255) check (...)`, que no es SQL válido.
- **Nombre de la restricción**: el automático sería `flavors_tobacco_type_check`, sin comprobar en producción. Un `DROP CONSTRAINT IF EXISTS` con un nombre equivocado no da error y deja activa la lista antigua. Por eso RF-51 exige consultar `pg_constraint`.
- **MySQL**: es un `enum` nativo; se redefine la columna con los 5 valores, manteniéndola `nullable`.
- **SQLite**: `enum` es una restricción CHECK y `->change()` reconstruye la tabla con `DROP TABLE`. `compileAlter` desactiva las claves foráneas con un `PRAGMA`, pero SQLite lo ignora dentro de una transacción. Con filas hijas, `favorites` (en cascada) perdería filas y `mix_items` (restricción) haría fallar la migración. Con `RefreshDatabase` las tablas están vacías y el problema no se ve. El plan debe decidir cómo evitarlo (por ejemplo, `$withinTransaction = false` con una transacción explícita en la rama de Postgres) y aislar el test de RF-51 y RF-52 del resto.
- **Datos**: los valores actuales de producción están limitados a los 4 antiguos, que siguen siendo válidos.
- **Riesgo de despliegue**: el arranque es `... migrate --force && php artisan serve ...`; si la migración falla, la aplicación no arranca. Antes del push, el usuario puede confirmar la restricción real con una consulta de solo lectura a `pg_constraint` en Railway.

**Tests:**
- Base de datos: SQLite en memoria, ya configurada en `phpunit.xml`, con `pdo_sqlite` activo en el `php.ini` de XAMPP. `pdo_pgsql` está desactivado.
- Organización: un archivo por área en `tests/Feature/`, con `RefreshDatabase`, y se mantiene el test de ejemplo.
- Factories: solo existe la de `User`. Hacen falta para marcas, categorías, sabores y mezclas, junto con `HasFactory` en esos modelos.
- **GD**: está desactivado en el `php.ini` local y `UploadedFile::fake()->image()` lanza una excepción sin él. No hace falta activarlo:
  - `UploadedFile::fake()->create('foto.jpg', 2048, 'image/jpeg')` no lo usa, y sirve para formatos y límites de tamaño.
  - El contenido real se prueba con un `UploadedFile` sobre un archivo temporal: bytes de un PNG válido escritos desde base64 (aceptado) y texto con nombre `.jpg` (rechazado). `fileinfo` está activo.
  - RF-64 se prueba con un `UploadedFile` con error `UPLOAD_ERR_INI_SIZE`. Laravel añade entonces el fallo `uploaded` si el campo tiene reglas de archivo.
- **RF-12b y RF-55 en SQLite**: un test con `DB::listen` que compruebe que el valor no válido no llega como parámetro sí puede empezar en rojo, y es preferible a un test que solo ve 0 resultados.

**Lo que SQLite no detecta:**
- Ids no numéricos o desbordados: solo dan error en Postgres.
- `varchar(n)` no impone longitud en SQLite: los límites se prueban en la validación.
- `decimal(5,2)` devuelve `50` en SQLite y `50.00` en MySQL y Postgres. RF-30 se comprueba en el navegador contra el MySQL local.

**Otros puntos:**
- `name[]=x`: además de la consulta, `value="{{ request('name') }}"` del formulario falla con un array. Hay que tratar también la vista.
- El `.env` local tiene `APP_LOCALE=en`, `.env.example` también y no existe carpeta `lang/`. La paginación de Bootstrap usa `__('Showing')` y `pagination.previous`, así que RF-60 y RF-63 no pueden depender solo de traducciones por idioma sin fijarlo en el código.
- `upload_max_filesize` y `post_max_size` valen 40 MB en local; los de Railway no están comprobados.
- Seeder de usuarios: la copia original está en `.claude/database/seeders/`. La nueva va en `database/seeders/`. RF-65 es un seeder aparte.
