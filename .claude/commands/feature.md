---
description: Planifica una nueva funcionalidad antes de tocar código
argument-hint: "[funcionalidad]"
---
Quiero añadir esta funcionalidad: $ARGUMENTS

Ten en cuenta el estado actual del proyecto: @MEMORY.md

Antes de escribir código, prepárame un plan con:
1. Cómo la vas a implementar, respetando las reglas de CLAUDE.md.
2. Qué archivos vas a modificar y qué cambia en cada uno. Marca aparte los archivos nuevos que habría que crear, incluidas las migraciones.
3. Si toca la base de datos: qué migraciones harían falta, cómo afectan a los datos que ya hay en producción y que todo funcione en Postgres.
4. Cómo afecta a cada tipo de usuario: sin sesión, usuario normal y admin.
5. Los casos límite y las dudas que debo decidir yo antes de empezar.
6. Cómo comprobarás que funciona, según la sección de Verificación de CLAUDE.md.
7. Qué actualizarías en CLAUDE.md y en MEMORY.md.

No modifiques ningún archivo hasta que apruebe el plan.