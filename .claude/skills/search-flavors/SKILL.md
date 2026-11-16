---
name: search-flavors
description: Busca en el BOE los sabores de cachimba publicados hasta hoy de las marcas que hay en la app y lista los que faltan en la base de datos
argument-hint: "[desde AAAA-MM-DD]"
disable-model-invocation: true
allowed-tools: Read, Grep, Glob, WebSearch, WebFetch, Bash(php artisan tinker:*)
---
Quiero saber qué sabores de cachimba han salido al mercado y todavía no están en UrruShisha.

Fecha de inicio: $ARGUMENTS
Si no indico fecha, usa la de "Última revisión del BOE" de MEMORY.md. Si tampoco hay, pregúntame desde qué fecha buscar antes de empezar.
Fecha de fin: hoy (la fecha actual en el momento de ejecutar la skill).

NO modifiques la base de datos ni ningún archivo del código. Esta skill solo consulta y te devuelve una lista.

## 1. Marcas y sabores de la app
Obtén con una consulta de SOLO LECTURA todas las marcas y, de cada una, todos sus sabores:
php artisan tinker --execute="echo App\Models\Brand::with('flavors:id,brand_id,name')->get(['id','name'])->toJson();"
Si el comando falla o devuelve una lista vacía, PARA y avísame: no sigas con datos incompletos.

## 2. Resoluciones del BOE
- Busca en boe.es las resoluciones de la Presidencia del Comisionado para el Mercado de Tabacos "por la que se publican los precios de venta al público de determinadas labores de tabaco" publicadas entre la fecha de inicio y hoy.
- Revisa TODAS las del periodo (suelen publicarse cada semana), no solo las primeras que encuentres. Anota la fecha y el identificador (BOE-A-AAAA-NNNNN) de cada una.
- En cada resolución, lee solo el apartado de "Picaduras de pipa" (es donde aparece el tabaco de cachimba), en todos los ámbitos que traiga (península e Illes Balears, Canarias, Ceuta y Melilla).
- El formato de cada línea es: Marca Sabor (peso g). precio. Ejemplo: "Tangiers 70 (250 g). 37,00".

## 3. Filtrar y comparar
- Quédate solo con las líneas de las marcas que existen en la app. Ignora el resto de marcas.
- Separa marca y sabor, y quita el peso y el precio. Un mismo sabor en varios pesos cuenta como uno solo.
- Compara sin distinguir mayúsculas, tildes, guiones ni espacios ("Love 66" = "love66").
- Si un sabor se parece mucho a uno de la app pero no es igual (por ejemplo, "Ice Mint" y "Mint Ice"), no lo des por nuevo ni por existente: ponlo en "Dudosos".

## 4. Respuesta
Devuélveme en markdown:

### Sabores que faltan
Agrupados por marca, en orden alfabético. En cada sabor: nombre tal y como aparece en el BOE, pesos publicados y la resolución donde salió (fecha e identificador con su enlace).

### Dudosos
Los sabores parecidos a uno que ya está en la app, indicando con cuál se parecen.

### Resumen
- Periodo revisado y número de resoluciones consultadas.
- Marcas de la app sin ninguna novedad.
- Cualquier resolución que no hayas podido leer.

Al terminar, actualiza en MEMORY.md la línea "Última revisión del BOE: AAAA-MM-DD" con la fecha de hoy (créala si no existe).