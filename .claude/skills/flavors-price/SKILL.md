# Rellenar y revisar precios de una marca en UrruShisha

Marca: $ARGUMENTS

## Objetivo
Revisa todos los sabores de esta marca en la web:
- Si un sabor NO tiene precio, búscalo y rellénalo.
- Si un sabor YA tiene precio, comprueba que sea correcto. Si lo es, déjalo
  como está; si no, corrígelo.

## Paso 1: entender el campo
Antes de buscar nada, mira en el código (modelo, migración y formulario de
admin de sabores) cómo es el campo de precio: tipo, formato, moneda y si
corresponde a un tamaño concreto (precio por lata, por gramos, rango...).
Si no está claro a qué corresponde el precio, pregúntame antes de seguir.

Ya revisado (nov. 2026): cada sabor tiene VARIOS precios, uno por cantidad
(tabla `flavor_prices`: `grams` entero 1-5000 y `price` en € con 2
decimales, único por sabor y cantidad). En el formulario son las filas
`prices[i][grams]` y `prices[i][price]`. Solo hay que volver a mirarlo si
cambia el código.

## Paso 2: listar los sabores
Saca la lista de todos los sabores de la marca con su precio actual (o
vacío, si no tienen).

Cómo (Chrome DevTools, con sesión de admin en
<https://proyectourrushisha-production.up.railway.app/>; si no la hay, PARA
y pide al usuario que inicie sesión): con `evaluate_script`, coger el id de
la marca de `#brand_id` en `/admin/flavors/create`, leer
`/admin/flavors?brand_id=<id>&sort=name_asc` (filas `tbody tr`; columnas:
id, nombre, marca, categoría, tipo, público) y, por cada fila, abrir su
enlace `/edit` y leer los inputs `prices[i][grams]` / `prices[i][price]`.

## Paso 3: buscar los precios
- Busca el precio en euros y en España, en el formato que pide el campo.
- **Fuente principal: el BOE.** En España el precio del tabaco está
  regulado: las resoluciones del Comisionado para el Mercado de Tabacos
  publican el PVP oficial de cada sabor y formato, en la tabla "PICADURAS DE
  PIPA". Cada fila tiene este formato: `Anda Tropido (200 g).` → `17,50`. Un
  PVP que se repite en varias resoluciones cuenta como dato seguro. Las
  tiendas españolas no pueden vender tabaco por internet y casi nunca
  publican precios, así que no sirven para contrastar.
- Scripts (Node, en esta carpeta; ejecutar desde un directorio del
  scratchpad, no desde aquí):
  1. `boe-resolutions.json`: lista de resoluciones de precios del
     05/01/2019 al 07/11/2026. Para añadir las nuevas:
     `node <skill>/boe-scan.cjs <día siguiente al último> <hoy> nuevas.json`
     (recorre los sumarios diarios del BOE). Los scripts son `.cjs` porque
     el `package.json` del proyecto tiene `"type": "module"`.
  2. `node <skill>/boe-prices.cjs "<prefijo>" salida.json <skill>/boe-resolutions.json [nuevas.json]`
     saca todas las filas de picaduras de pipa que empiezan por ese
     prefijo, con fecha, id del BOE y precio, ordenadas por fecha. Tarda
     unos minutos.
  3. Usa SOLO las filas con `area: "Península y Baleares"`. Cada resolución
     tiene otra parte para Ceuta y Melilla, con precios más bajos (por
     ejemplo, Adalya 50 g: 3,85 en la Península y 2,20 en Ceuta y Melilla).
     En resoluciones antiguas (2021 o antes) la zona puede salir como
     `desconocida`: si hay dos precios para la misma fila, el más alto es el
     de la Península.
  4. El precio actual de cada sabor y formato es el de la resolución MÁS
     RECIENTE en la que aparece. Ojo: una subida general de la marca cambia
     también los sabores antiguos.
- Prefijo y trampas conocidas por marca:
  - Andalushisha → prefijo `Anda`. La línea "Anda Blyat Strong …" es otra
    gama, con otro precio (en la web es la marca Blyat). "Anda Temptation
    Mandingo" = Mandingo. En el BOE pone "Sour Dia" y en la web "Soul Dia"
    (en nov. 2026 se trataron como el mismo sabor). Precio desde el
    28/12/2024: 50 g 4,95, 200 g 17,50, 950 g 75,00.
  - Blyat → prefijo `Anda Blyat Strong` (no hay líneas "Blyat …" sueltas).
    Comparte nombres con Andalushisha (Clipporizz, Mentido, Temptation,
    Tiki Taka, Optimus Leim), pero son otras filas con otro precio. Precio
    de toda la gama (mar. 2025 - may. 2026): 50 g 6,50, 200 g 22,50; sin
    950 g.
  - Adalya → prefijo `Adalya`. Precio desde el 11/01/2025: 50 g 3,85,
    200 g 14,00, kilo 65,00 (latas de 1000 g y de 990 g; se usa la que
    publique el BOE para ese sabor, y la más reciente si salen las dos). Pocos
    sabores tienen 200 g o kilo. Nombres en el BOE: "Gipsy Kings" = Gipsy
    King, "L. Kill" = Lady Killer ("L. Kill 2.0" es otro), "Tynky Wynky" =
    Tinky Winky, "Love66" = Love 66, "Manko Tanko" y "Manko Tanko I'ss" =
    MANKO TANGO (no se sabe cuál de los dos). M.M no aparece.
  - Al Fakher → prefijo `Al ?Fakher` (en la web hay otra marca, "Al Fakher
    x Snoop Dogg"). NO tiene precio único; tiene varias gamas:
    - clásica: 50 g 4,85 (subida del 05/09/2026), 200 g 17,50, 900 g 69,95.
      Algunos clásicos se quedaron en 4,70 porque no entraron en la subida
      de 2026; en nov. 2026 se dejaron SIN precio (dudosos);
    - "Dreams"/moda (Catwalk Melody, Runway Rhapsody, Indigo Crescendo…):
      50 g 4,80;
    - Golden (Ivory Gold, Polar Freeze): 100 g 9,95, 200 g 19,95;
    - "TPD F" (2022) son los mismos sabores con el envase antiguo. 250 g y
      1000 g se sustituyeron por 200 g y 900 g en febrero de 2025. 5000 g es
      de hostelería: no se pone.
    - Dudosos: Black Mist (solo 250 g 16,90 y 1000 g) y Magic Love 2.0
      ("Magic Love" y "Magic Love Crafted Mixes" tienen precios distintos).
  - Si una marca no sale con su nombre, probar `boe-prices.cjs` con un
    prefijo corto o con parte del nombre de la marca, y revisar las filas
    para descubrir cómo la escribe el BOE.
- Si el BOE no da el dato, usa como fuentes secundarias la web oficial de la
  marca y tiendas especializadas españolas.
- Mira varias fuentes y compara. No te quedes con el primer resultado.
- No des un precio por bueno hasta que se cumpla todo esto:
  - sale en al menos dos o tres tiendas o fuentes,
  - corresponde al mismo formato o tamaño que pide el campo,
  - es actual (nada de ofertas puntuales ni páginas antiguas).
- Si los precios varían, usa el precio habitual (PVP), o un rango si el
  campo lo permite.
- Muchas marcas tienen el mismo precio para todos sus sabores. Si es así,
  compruébalo una vez, pero confirma que no haya sabores o líneas con un
  precio distinto (ediciones especiales, gamas premium, otros tamaños).

## Paso 4: decidir qué hacer con cada sabor
- Sin precio y con un dato seguro → rellenarlo.
- Sin precio y sin un dato seguro → dejarlo vacío. EXCEPCIÓN (decisión del
  usuario, nov. 2026): el precio va por marca. Si un sabor no sale en el
  BOE pero todos los de su marca tienen el mismo precio, ponle ese precio
  en los formatos comunes de la marca (por ejemplo, 50 g y 200 g), sin
  formatos raros como el kilo. Indícalo en la tabla. Esto solo vale si la
  marca tiene un precio único. Si tiene varias gamas o hay cualquier duda,
  el sabor se queda SIN precio (decisión del usuario con Al Fakher).
- Con precio y el precio coincide con lo encontrado (o la diferencia es
  mínima y entra en el rango normal entre tiendas) → dejarlo.
- Con precio, pero el precio es claramente distinto a lo encontrado (precio
  antiguo, otro formato, error) y el dato nuevo es seguro → modificarlo.
- Con precio, pero no puedes confirmar ni desmentir el precio actual → NO lo
  toques y márcalo como dudoso.

## Paso 5: confirmar y aplicar
1. Antes de cambiar nada, enséñame una tabla con estas columnas: sabor,
   precio actual, precio encontrado, acción (rellenar / modificar / dejar /
   dudoso) y fuentes (enlaces).
2. Espera mi OK.
3. Aplica los cambios desde el formulario de edición del admin (el método
   está documentado en `.claude/skills/flavors-create/SKILL.md`). Probado en
   nov. 2026, con `evaluate_script`, por cada sabor:
   - `fetch('/admin/flavors/<id>/edit')`, parsear con `DOMParser` y coger
     el formulario cuyo `_method` es `PUT`.
   - `new FormData(form)` (así se conservan TODOS los datos actuales: al
     guardar se sustituyen todos los campos y todos los precios), quitar
     `image` y las claves `prices[...]`, y añadir las filas nuevas
     (`prices[0][grams]=50`, `prices[0][price]=4.95`…). Si el sabor ya tiene
     precios que hay que conservar, volver a incluirlos.
   - `POST` a la `action` del formulario. Si la respuesta contiene "Sabor
     actualizado correctamente." se ha guardado. Si no, anotar el error y no
     reintentar.
   - Un sabor sin categoría no se puede guardar (la categoría es
     obligatoria): avisar al usuario, no ponerle una por tu cuenta.
4. Comprueba en la web que los precios se han guardado y se ven bien:
   relee los `/edit`, y en la portada (`/?brand_id=<id>&name=<sabor>`) la
   ventana del sabor muestra "Precios 50 g · 4,95 €…".

## Al terminar
Dame un resumen con los sabores rellenados, los modificados (con el precio
antiguo y el nuevo), los que se han quedado igual y los que siguen vacíos o
dudosos.