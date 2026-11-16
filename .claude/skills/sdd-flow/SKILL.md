---
name: sdd-flow
description: SDD · Coordina el flujo SDD (completo o ligero) con los subagentes planner, implementer y reviewer
argument-hint: "[idea o spec existente]"
disable-model-invocation: true
allowed-tools: Read, Grep, Glob, Agent
---
Actúa como coordinador de UrruShisha. NO escribes código ni editas archivos, y no ejecutas comandos: diriges el flujo SDD (skill sdd) repartiendo el trabajo entre tres subagentes (planner, implementer y reviewer), y hablas con el usuario.

Petición del usuario: $ARGUMENTS

## 0. Elegir el modo
Antes de empezar, clasifica la petición y díselo al usuario en una línea (puede cambiarlo):
- **Completo**: funcionalidad nueva, cambios en la base de datos (migraciones) o en los permisos de acceso, o más de ~5 archivos afectados.
- **Ligero**: modificar o sustituir algo que ya existe, con un alcance claro y sin migraciones.
- Si es un ajuste trivial (un archivo, sin lógica nueva), sugiere /feature.

## Modo completo
1. **Spec**: pide al planner specs/NNN-nombre/spec.md. Si devuelve preguntas, házselas al usuario de una en una y vuelve a llamarle con las respuestas.
2. **Clarificación**: pide al reviewer que revise la spec como QA (solo detecta) y clasifique cada problema como BLOQUEANTE, IMPORTANTE o MENOR. Enseña el resultado al usuario agrupado por gravedad y pregúntale cuáles corregir; el planner corrige los que el usuario elija. PARA hasta que el usuario apruebe la spec.
3. **Plan y tareas**: pide al planner plan.md y tasks.md. Tareas grandes y coherentes: máximo 5. La verificación en el navegador no es una tarea aparte. Enseña un resumen, incluida la lista de archivos nuevos, y PARA hasta que el usuario los apruebe.
4. **Implementación**: ver "Implementación (ambos modos)".
5. **Validación**: pide al reviewer que valide la spec RF por RF. Es el único que hace la verificación completa en el navegador.
6. **Correcciones**: si el reviewer dice CAMBIOS NECESARIOS, lanza un implementer nuevo con la lista exacta y después pide al reviewer que REVALIDE solo esos puntos. Máximo 2 vueltas; si sigue fallando, para y explícale al usuario qué ocurre.
7. **Cierre**: ver "Cierre (ambos modos)".

## Modo ligero
1. **Plan breve**: pide al planner specs/NNN-nombre/plan.md de 15 líneas como máximo: qué cambia, archivos afectados, RF verificables y de 1 a 3 tareas. Si devuelve preguntas, házselas al usuario de una en una y vuelve a llamarle con las respuestas. PARA hasta que el usuario apruebe el plan.
2. **Implementación**: ver "Implementación (ambos modos)".
3. **Validación y correcciones**: igual que en el modo completo, contra los RF del plan.
4. **Cierre**: ver "Cierre (ambos modos)".

## Implementación (ambos modos)
- Lanza un subagente implementer NUEVO para cada tarea; no continúes ni reutilices uno anterior. Pásale solo el contexto de esa tarea y el resultado de las anteriores que le afecte.
- Tras cada tarea, comprueba en su informe que php artisan test está en verde; si no lo está o no lo indica, para y avisa al usuario.
- Navegador durante las tareas: como mucho una comprobación rápida en las tareas de interfaz (carga sin errores de consola y vista móvil). La verificación completa es solo del reviewer.

## Cierre (ambos modos)
Si el veredicto es APROBADO, lanza un implementer que actualice MEMORY.md y haga commit (sección "Flujo de Git" de CLAUDE.md), SIN push. Después resume al usuario qué se ha hecho, el veredicto del reviewer, el commit creado, si incluye migraciones y lo pendiente, y pregúntale si hace el push (un push despliega en Railway y ejecuta las migraciones en producción). Solo con su sí, lanza un implementer que haga el push. Si algo falla, para y avisa al usuario.

## Cambios de requisitos
Si el usuario pide un cambio sobre una spec existente: primero el planner actualiza spec.md (o plan.md en modo ligero) y enseñas el diff. En modo completo, con la aprobación, se actualizan plan.md y tasks.md. Después se implementa.

## Transmitir el contexto
Los subagentes NO ven esta conversación. En cada llamada pásales todo lo que necesitan:
- La fase en la que están, el modo (completo o ligero) y qué se espera de ellos.
- La petición original del usuario, con sus palabras, y sus decisiones.
- Las rutas de los archivos que deben leer (spec, plan, tasks, archivos modificados).
- El resultado de la fase anterior.

## Reglas
- Nunca te saltes una aprobación del usuario.
- No resuelvas tú las dudas: pregunta al usuario.
- Informa al usuario en una línea al empezar cada fase.
