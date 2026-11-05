# Spec 002 — Logo nuevo, fotos de Favoritos y mezclas en grande
Estado: aprobada

## Contexto y objetivo

El usuario quiere tres mejoras visuales:

1. Cambiar el logo por la versión nueva, con el nombre en mayúsculas ("URRUSHISHA"). El archivo lo ha facilitado el usuario: `LOGO-URRUSHISHA (1).png`.
2. En Favoritos, el hueco de la foto tiene un fondo lavanda y un margen interior que no coinciden con el blanco de la tarjeta, y se nota el corte. La foto debe verse entera, sobre el mismo fondo que la tarjeta.
3. En Mezclas, poder abrir una mezcla en grande, a modo de tarjeta, y que las fotos de los sabores del listado sean algo más grandes.

Ninguna de las mejoras cambia los datos guardados, las rutas ni los permisos.

## Usuarios

- **Visitante sin sesión**:
  - Ve el logo nuevo en login y registro.
  - Ve el logo de la barra superior en las páginas de error 403 y 404. En login y registro la barra superior no se muestra.
  - Ve el icono de la pestaña.
- **Usuario registrado**: ve el logo nuevo en la barra superior, los favoritos con el fondo nuevo, y sus mezclas con las fotos más grandes y la ventana de detalle.
- **Admin**: lo mismo que el usuario registrado. No tiene nada específico en esta spec.

## Historias de usuario

- HU-1. Como visitante o usuario, quiero ver el logo nuevo en toda la web para que la imagen de marca sea la actual.
- HU-2. Como usuario registrado, quiero ver la foto entera de mis favoritos, sin un fondo distinto al de la tarjeta, para reconocer la lata y que la tarjeta se vea limpia.
- HU-3. Como usuario registrado, quiero pulsar una mezcla y verla en grande para ver mejor sus sabores y proporciones.

## Definiciones

- **Logo nuevo**: la imagen del archivo `LOGO-URRUSHISHA (1).png` que ha facilitado el usuario: PNG con transparencia, de 748x640 px y 311 KB.
- **Ventana de la mezcla**: ventana emergente que se abre encima del listado de Mezclas. Se comporta como la ventana de detalle de sabor (abrir y cerrar), pero con el aspecto de la tarjeta de mezcla del listado: oscura y translúcida, con el texto en blanco. No usa el aspecto claro de la ventana de sabor.

## Requisitos funcionales

### Logo

Nota para el plan: el plan decidirá si se usa el PNG original o copias redimensionadas que conserven los nombres actuales. Los nombres y tamaños actuales son los del logo de la barra (176x151 px), el logo grande de login y registro (400x342 px) y el favicon (64x55 px). Hay que tener en cuenta dos limitaciones: GD está desactivado en local y no se pueden añadir dependencias. El logo antiguo que no se usa en ninguna pantalla (1,8 MB) no se toca.

- RF-1: EL SISTEMA muestra el logo nuevo en la barra superior, en lugar del actual, y mantiene a su lado el texto "URRUSHISHA". Esto se aplica en todas las pantallas que muestran esa barra, también en las páginas de error 403 y 404 para el visitante sin sesión.
- RF-2: EL SISTEMA muestra el logo nuevo en las pantallas de login y registro, en lugar del logo grande actual.
- RF-3: EL SISTEMA usa el logo nuevo como icono de la pestaña del navegador (favicon), en lugar del actual.
- RF-4: EL SISTEMA muestra el logo de la barra superior y el de login y registro con el mismo tamaño en pantalla que el logo actual. El tamaño del favicon no se exige: lo decide el navegador.

### Favoritos

- RF-5: MIENTRAS un favorito tiene foto, EL SISTEMA la muestra entera en su tarjeta de Favoritos, sin recortarla.
- RF-6: EL SISTEMA da al hueco de la foto de cada tarjeta de Favoritos el mismo fondo blanco que la tarjeta, sin degradado lavanda y sin margen interior entre el borde del hueco y la foto.
- RF-7: MIENTRAS un favorito no tiene foto, o la foto no carga, EL SISTEMA muestra el mismo marcador con la inicial que ahora.
- RF-8: EL SISTEMA mantiene sin cambios las fotos del catálogo y las de la ventana de detalle de sabor, tanto en el catálogo como en Favoritos.

### Mezclas: listado

- RF-9: EL SISTEMA muestra la foto de cada sabor del listado de Mezclas con unos 64 px de lado (ahora mide unos 46 px). Mantiene el fondo actual de la foto (lavanda claro, con 3 px de margen interior) y el borde del color de su tramo de la barra.
- RF-10: CUANDO el usuario pulsa una tarjeta de mezcla, fuera de los botones Editar y Eliminar, EL SISTEMA abre la ventana de esa mezcla.
- RF-11: CUANDO la tarjeta de una mezcla tiene el foco del teclado y el usuario pulsa Intro, EL SISTEMA abre la ventana de esa mezcla. Funciona igual que en las tarjetas de Favoritos: la barra espaciadora no abre la ventana y no se exige.
- RF-12: CUANDO el usuario pulsa Editar o Eliminar en la tarjeta, EL SISTEMA hace la acción del botón como ahora y no abre la ventana.

### Mezclas: ventana

- RF-13: EL SISTEMA muestra la ventana de la mezcla con el aspecto de la tarjeta de mezcla del listado: fondo oscuro y translúcido y texto blanco. La ventana incluye:
  - el nombre y si es pública o privada;
  - la barra de porcentajes;
  - cada sabor, con su foto, nombre, marca y porcentaje;
  - las notas, si las tiene;
  - la fecha de creación. Si la mezcla no tiene fecha de creación, no se muestra, igual que en el listado.

  La barra y los bordes de las fotos llevan los mismos colores que esa mezcla tiene en el listado.
- RF-14: EL SISTEMA muestra la foto de cada sabor de la ventana con unos 96 px de lado, entera y sin recortar. Mantiene el mismo fondo, margen interior y borde que en el listado (RF-9).
- RF-15: EL SISTEMA muestra en la ventana un botón Editar que lleva a la pantalla de edición de esa mezcla.
- RF-16: EL SISTEMA muestra en la ventana un botón Eliminar que pide la misma confirmación que el del listado.
  - Si el usuario cancela la confirmación, la ventana sigue abierta y no se borra nada.
  - Si la confirma, la mezcla se elimina como ahora y se vuelve al listado con el mismo mensaje de éxito que ahora.
- RF-17: CUANDO el usuario pulsa el botón de cerrar, pulsa fuera de la ventana o pulsa Escape, EL SISTEMA cierra la ventana y deja el listado como estaba. No se exige devolver el foco a la tarjeta al cerrar, igual que en Favoritos.
- RF-18: MIENTRAS la ventana está abierta, EL SISTEMA impide que el listado de debajo se desplace.
- RF-19: EL SISTEMA muestra en la ventana solo mezclas que ya aparecen en el listado del usuario, es decir, las suyas. No se añaden rutas, datos ni permisos nuevos.

## Requisitos no funcionales

- RNF-1: La ventana de la mezcla y las tarjetas de Favoritos y Mezclas se ven bien a 375 px de ancho, sin scroll horizontal, y la ventana cabe en la pantalla, con desplazamiento interior si hace falta.
- RNF-2: No se añaden dependencias, migraciones ni columnas nuevas.
- RNF-3: Los textos de la interfaz siguen en español. La ventana tiene un botón de cerrar con la etiqueta accesible "Cerrar".
- RNF-4: Las páginas cambiadas cargan sin errores en la consola del navegador.

## Casos límite

- Una mezcla sin sabores: la ventana se abre, sin barra y con el texto "Esta mezcla no tiene sabores.", igual que en el listado.
- Un sabor borrado dentro de una mezcla: se muestra como "Sabor no disponible", con la inicial "?", igual que en el listado.
- Un sabor sin foto o con una foto que no carga: se muestra el marcador con la inicial, tanto en el listado como en la ventana.
- Una mezcla sin fecha de creación: no se muestra la fecha, ni en el listado ni en la ventana.
- Nombres de mezcla, sabor o notas muy largos: el texto se corta en varias líneas dentro de la ventana, sin salirse de ella.
- Un porcentaje muy pequeño (1 %): su tramo sigue siendo visible en la barra de la ventana, igual que en el listado.
- Una foto de favorito con su propio fondo pintado (por ejemplo, un damero falso o un color distinto del blanco): el fondo de la foto se seguirá notando, pero no se arregla en esta spec (ver Fuera de alcance).

## Fuera de alcance

- Arreglar las fotos de sabores que traen su propio fondo pintado: la spec solo cambia el fondo y el margen del hueco de la foto en Favoritos. Esas fotos se arreglan subiendo otra imagen desde el admin.
- Cambiar las fotos del catálogo o de la ventana de detalle de sabor, y el fondo de las fotos de Mezclas.
- Una página propia o una dirección para cada mezcla, compartir mezclas o ver las de otros usuarios.
- Cambiar los colores de la barra de porcentajes: se mantienen los 6 colores fijos actuales, asignados según la posición del sabor en la mezcla.
- Color de la barra según la categoría del sabor: queda para más adelante. Cuando se retome, el usuario ya ha decidido que los sabores de la misma categoría que se repitan en una mezcla llevarán tonos distintos del mismo color.
- Abrir la ventana con la barra espaciadora y devolver el foco a la tarjeta al cerrarla.
- Borrar o sustituir el logo antiguo que no se usa en ninguna pantalla.
- Cambiar cómo se crean, editan o validan las mezclas.
- Cambiar el texto "URRUSHISHA" de la barra o la estructura de la navegación.

## Criterios de finalización

- Se cumplen RF-1 a RF-19 y RNF-1 a RNF-4.
- `php artisan test` pasa.
- El reviewer comprueba en el navegador, a 375 px y en escritorio y sin errores en la consola:
  - el logo de la barra con sesión y en las páginas 403 y 404 sin sesión;
  - el logo de login y registro;
  - el favicon de la pestaña. El navegador lo guarda en caché, así que puede hacer falta recargar sin caché o abrir una ventana privada para ver el nuevo;
  - la tarjeta de Favoritos;
  - el listado de Mezclas;
  - la ventana de la mezcla: abrir con clic e Intro, cerrar con el botón, al pulsar fuera y con Escape, Editar, y Eliminar tanto cancelando como confirmando.

## Dudas abiertas

Ninguna.
