---
name: search-flavors-by-brand
description: Busca en el BOE todos los sabores de una marca, los compara con la web y añade los que faltan desde el modo admin, sin publicar
argument-hint: "[marca]"
disable-model-invocation: true
allowed-tools: Read, WebSearch, WebFetch, mcp__chrome-devtools
---
Marca: $ARGUMENTS
Web de producción: <https://proyectourrushisha-production.up.railway.app/>

Objetivo: encontrar los sabores de esta marca publicados en el BOE que no están en la web y añadirlos desde el modo admin, rellenando cada campo solo con datos verificados y SIN publicarlos, para que yo los revise y complete.

## Reglas
- NUNCA te inventes un dato. Cada valor que escribas debe venir de una fuente concreta que puedas citar. Si no lo encuentras, se aplica la tabla del paso 4.
- NUNCA marques "Público": todos los sabores se crean ocultos.
- Nunca escribas contraseñas. Si no hay sesión de admin iniciada en el navegador, PARA y pídeme que inicie sesión yo en esa ventana.
- Solo puedes crear sabores. No edites ni borres sabores, marcas ni categorías.
- No subas imágenes.

## 1. Sabores de la marca en el BOE
- Busca en boe.es todas las resoluciones de la Presidencia del Comisionado para el Mercado de Tabacos que publican "precios de venta al público de determinadas labores de tabaco" y que mencionen esta marca (usa el buscador del BOE con el nombre de la marca, en todos los años disponibles).
- En cada resolución, lee el apartado de "Picaduras de pipa". Formato: Marca Sabor (peso g). precio. Ejemplo: "Tangiers Cane Mint (250 g). 37,00".
- Extrae el nombre del sabor sin la marca, el peso ni el precio. Un sabor publicado en varios pesos o en varias resoluciones cuenta como uno solo. Anota la resolución (fecha e identificador BOE-A-AAAA-NNNNN) donde aparece por primera vez.

## 2. Sabores de la marca en la web
- Abre la web con Chrome DevTools, entra en el listado de sabores del modo admin y anota TODOS los sabores de esta marca (públicos y ocultos), recorriendo todas las páginas del listado.
- Si la marca no existe en la web, PARA y avísame: no la crees.

## 3. Comparar
- Compara sin distinguir mayúsculas, tildes, guiones ni espacios.
- Si un sabor del BOE se parece a uno de la web pero no es igual (por ejemplo, "Ice Mint" y "Mint Ice"), no lo añadas: ponlo en "Dudosos".
- Muéstrame la lista de sabores que faltan y los dudosos, y PARA hasta que yo apruebe qué sabores añadir.

## 4. Investigar cada sabor aprobado
Busca a fondo en internet: la web oficial de la marca y sus redes sociales, tiendas online de cachimbas y reseñas. Escribe cada campo así:

| Campo | Qué poner | Si no hay información |
|---|---|---|
| Marca | La marca indicada | — |
| Nombre | El nombre tal y como aparece en el BOE | — |
| Categoría | Solo si una fuente deja claro el tipo de sabor (fruta, menta, postre…) y coincide con una categoría que ya existe en la web | Categoría "Sin información". Si no existe, PARA y pídeme que la cree |
| Tipo de tabaco | Solo uno de estos valores, si una fuente lo confirma: rubio, negro, herbal, base, rojo, sin_nicotina | Déjalo VACÍO (cualquier otro texto da error al guardar) |
| Ingredientes | Solo los que una fuente indique expresamente | Sin información |
| Descripción | Resumen breve en español, con tus palabras, de lo que dicen las fuentes sobre el sabor. No copies textos | Sin información |
| Imagen | No subas ninguna | — |
| Público | Sin marcar | — |

Si dos fuentes se contradicen en un dato, no elijas tú: usa "Sin información" (o vacío) y anótalo en el informe.

## 5. Añadir los sabores
- Antes de crear cada sabor, comprueba otra vez en el listado del admin que no exista ya.
- Créalo desde el formulario de nuevo sabor del modo admin y comprueba que aparece el mensaje de que se ha creado correctamente. Si da error, no lo reintentes: anótalo y sigue con el siguiente.

## 6. Informe final (en markdown)
### Sabores añadidos
Por cada sabor: nombre, resolución del BOE, los campos rellenados con la fuente (enlace) de cada dato, y los campos que han quedado en "Sin información" o vacíos.
### Dudosos
Sabores del BOE parecidos a uno que ya está en la web, y con cuál.
### No añadidos
Sabores que dieron error o que no aprobé, con el motivo.
### Resumen
Número de resoluciones revisadas, sabores en el BOE, en la web, añadidos y pendientes de completar por mí.