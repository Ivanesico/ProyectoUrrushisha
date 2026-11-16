# Crear sabores en UrruShisha

Te paso una lista de sabores: $ARGUMENTS

## Objetivo
Para cada sabor de la lista, busca sus datos y créalo en la web.
NO busques ni subas la imagen: de eso me encargo yo.

## Paso 0: método de creación (solo la primera vez)
> Estado: DECIDIDO (formulario del modo admin en producción, con Chrome DevTools)

Si arriba pone "SIN DECIDIR":
1. Revisa el proyecto (modelos, migraciones, controladores del modo admin,
   rutas, seeders) para saber qué campos tiene un sabor, cuáles son
   obligatorios y qué relaciones tiene (marca, categorías...).
2. Evalúa las formas posibles de crear sabores: interfaz del modo admin con
   Chrome DevTools/navegador, un comando Artisan, un seeder, peticiones HTTP
   al endpoint del admin, Tinker, etc.
3. Elige la más fiable y cómoda para ti. Ten en cuenta que la web está
   desplegada en Railway con Postgres: los sabores tienen que acabar en la
   base de datos de producción, no solo en local.
4. Reescribe ESTE archivo: cambia "SIN DECIDIR" por el método elegido y
   añade una sección "## Cómo crear un sabor" con los pasos exactos
   (comandos, URLs, campos, formato y cualquier truco que hayas descubierto),
   para no tener que volver a investigarlo la próxima vez.
5. Si para el método elegido necesitas crear código nuevo (un comando, un
   endpoint...), explícame qué vas a crear y espera mi confirmación antes de
   hacerlo.

Si ya hay un método decidido, sigue directamente los pasos de
"## Cómo crear un sabor".

## Cómo crear un sabor

### Por qué este método
- Los sabores tienen que acabar en el Postgres de Railway. Desde aquí no hay
  acceso a esa base de datos: Tinker, los seeders y los comandos Artisan
  solo escriben en el MySQL local. Un comando nuevo exigiría código y un
  deploy.
- El formulario del admin ya valida todo (`StoreFlavorRequest`) y guarda
  `created_by`. No hace falta código nuevo.

### Datos de un sabor (formulario `/admin/flavors/create`)
| Campo del formulario | Obligatorio | Qué es |
|---|---|---|
| `brand_id` (select "Marca") | Sí | Una marca que ya exista |
| `category_id` (select "Categoría") | Sí | Una categoría que ya exista. NO hay categoría "Sin información" (nov. 2026): si no se sabe, PREGUNTA al usuario (la vez anterior eligió "Bebida" para los sabores sin datos) |
| `name` | Sí | Nombre, máx. 180 caracteres |
| `tobacco_type_id` (select "Tipo de tabaco") | No | Un tipo de la tabla `tobacco_types` (los gestiona el admin). Vacío si no se sabe |
| `ingredients_text` | No | Texto, máx. 500 caracteres |
| `description` | No | Texto libre (en español, con palabras propias) |
| `image` | No | NO subir: la pone el usuario |
| `is_public` (checkbox "Público") | No | Dejar SIN marcar: el sabor se crea oculto hasta que el usuario ponga la imagen y lo revise |
| `prices[i][grams]`, `prices[i][price]` | No | No rellenar aquí: los precios van con la skill `flavors-price` |

### Pasos
1. Web: <https://proyectourrushisha-production.up.railway.app/>. Abrirla con
   Chrome DevTools (`list_pages` / `new_page`). Si no hay sesión de admin,
   PARA y pide al usuario que inicie sesión en esa ventana. Nunca escribir
   contraseñas.
2. Leer las opciones válidas: abrir `/admin/flavors/create` y, con
   `evaluate_script`, sacar el texto y el `value` de las opciones de
   `#brand_id`, `#category_id` y `#tobacco_type_id`. Usar esos ids, nunca
   suponerlos.
3. Comprobar que no exista: abrir
   `/admin/flavors?brand_id=<id>&name=<nombre>` (el filtro de nombre busca
   texto parecido, sin distinguir mayúsculas ni tildes) y revisar las filas.
   Mismo nombre y misma marca → saltar.
4. Tras el OK del usuario, crear cada sabor en `/admin/flavors/create`:
   rellenar con `fill` / `fill_form` (o `evaluate_script` poniendo `.value`
   en los campos), dejar "Público" sin marcar y pulsar "Guardar".
5. Comprobar el resultado: si redirige a `/admin/flavors` con el aviso
   "Sabor creado correctamente." está creado. Si vuelve al formulario con
   errores, anotar el mensaje y NO reintentar: seguir con el siguiente.

### Trucos
- Lo más rápido (probado en nov. 2026): con `evaluate_script` en una página
  del admin, por cada sabor, hacer `fetch('/admin/flavors/create')`, sacar
  el `_token` del formulario y enviar un `FormData` con `POST /admin/flavors`.
  Si el HTML de la respuesta contiene "Sabor creado correctamente." se ha
  creado. Al final, revisar `/admin/flavors?brand_id=<id>&sort=name_asc`.
- La comprobación de existencia también se hace con `fetch` del listado
  (filas en `tbody tr`).
- Ids actuales (pueden cambiar, compruébalos siempre): Taboo = 16. Tipo
  "Rubio" = 1 (corresponde a Virginia).
- Si el formulario se rellena con `evaluate_script`, en los `select` hay que
  asignar el `value` (el id), no el texto.
- Los caracteres especiales (`'`, `&`, `#`) se guardan tal cual. En el HTML
  del listado aparecen como `&#039;` o `&amp;`.

## Búsqueda de datos
- Usa los campos que tiene un sabor en la web; no inventes campos nuevos.
- Fuentes preferidas: web oficial de la marca, distribuidores y tiendas
  especializadas de cachimbas.
- Solo rellena un dato si estás seguro: tiene que aparecer en la web oficial
  o coincidir en al menos dos fuentes independientes.
- Si no lo encuentras o tienes dudas, deja el campo VACÍO. Nunca te lo
  inventes, aunque lo hagas a partir del nombre del sabor.
- Si la marca o la categoría no existe en la web, no la crees: avísame.

## Antes de crear
1. Comprueba que el sabor no exista ya (mismo nombre y misma marca). Si
   existe, sáltalo y avísame.
2. Enséñame una tabla con todos los sabores y los datos que vas a meter,
   indicando la fuente de cada dato, y espera a que te dé el OK.

## Al terminar
Dame un resumen con:
- los sabores creados,
- los sabores saltados (y el motivo),
- los campos que se han quedado vacíos en cada sabor (para que los revise).