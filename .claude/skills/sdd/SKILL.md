---
name: sdd
description: Úsala siempre que trabajes con Spec-Driven Development en este proyecto (docs/constitution.md o cualquier archivo dentro de specs/) - redactar, revisar o cambiar la constitución, specs, planes y tareas, o implementar y validar tareas de una spec.
---

# Spec-Driven Development (SDD)

## Flujo

Hay dos modos; el coordinador (sdd-flow) indica cuál se usa:

- **Completo**: Constitución → Spec → Clarificación → Plan → Tareas → Implementación → Validación → Cierre.
  Carpeta `specs/NNN-nombre/` con `spec.md`, `plan.md` y `tasks.md`.
- **Ligero**: Plan breve → Implementación → Validación → Cierre.
  Carpeta `specs/NNN-nombre/` solo con `plan.md`, que contiene los RF y las tareas.

## Reglas

- El usuario aprueba explícitamente la spec y el plan con las tareas (en modo ligero, el plan breve). Sin esa aprobación no se implementa.
- La spec manda (en modo ligero, el plan): si algo no está, no se implementa. Si falta una decisión, para y pregunta.
- Aprobar el plan cuenta como permiso para crear los archivos que el plan lista (incluidas migraciones y tests). Cualquier archivo nuevo que no esté en el plan se pregunta antes.
- Un cambio de requisitos se hace primero en la spec (o en el plan en modo ligero), luego en el plan y las tareas, y por último en el código.
- `MEMORY.md` se actualiza solo en el cierre. El progreso intermedio queda en las tareas marcadas como hechas.

## Plantillas

Están en la carpeta `plantillas/` de esta skill. Lee solo la que necesites para la fase actual:

- `plantillas/spec.md`: estructura de la spec y requisitos en EARS.
- `plantillas/plan.md`: contenido obligatorio del plan técnico.
- `plantillas/tasks.md`: formato y tamaño de las tareas.

El modo ligero no usa plantillas: el formato del plan breve está en la definición del planner.

## Implementación

Una sola tarea cada vez: tests primero (en rojo), después el código, `php artisan test` en verde, marcar la tarea y parar.

- Tests de Laravel en `tests/Feature/` con `RefreshDatabase` y factories. No dependas de datos que ya existan en la base de datos.
- Los tests corren en SQLite en memoria, no en Postgres. Que pasen no garantiza que funcionen en producción: revisa siempre el checklist de Postgres de abajo.
- Los cambios puramente visuales (solo Blade o estilos) no necesitan test; se comprueban en el navegador.

En el navegador, durante las tareas solo hay una comprobación rápida (consola sin errores y vista móvil de 375 px). La verificación completa la hace el reviewer, una sola vez, en la validación.

## Checklist de Postgres y migraciones

- [ ] ¿Algún `like` en búsquedas de texto? En Postgres distingue mayúsculas: usar `ilike` o comparar en minúsculas.
- [ ] ¿Algún SQL específico de MySQL (backticks, `IFNULL`, funciones propias)?
- [ ] ¿Se modifica una migración que ya existe? Prohibido: crear una nueva.
- [ ] ¿La migración nueva funciona con los datos que ya hay en producción (columnas nuevas `nullable` o con valor por defecto)?
- [ ] ¿Se guardan valores fuera de una lista cerrada (`enum`), como `tobacco_type`?
