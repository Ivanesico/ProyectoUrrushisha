---
name: sdd-constitution
description: SDD · Propone cambios en la constitución del proyecto (principios innegociables)
argument-hint: "[contexto adicional]"
allowed-tools: Read, Grep, Glob, Edit, Write
---
Vamos a revisar docs/constitution.md (o crearla, si no existe). Usa la skill sdd.

Antes de proponer nada, lee docs/constitution.md, CLAUDE.md, MEMORY.md y el código del proyecto.

Contexto adicional: $ARGUMENTS

Los principios deben ser innegociables, cortos y verificables, y cubrir: simplicidad del stack, relación entre spec y código, lógica fuera de las vistas, política de tests, protección de los datos de producción (Postgres y migraciones), permisos en el servidor e idioma del código y los textos. Máximo 7 principios y 15 líneas.

Propón el cambio como diff y NO escribas el archivo todavía: espera mi aprobación. Cuando la dé, escribe docs/constitution.md.

Si un cambio contradice CLAUDE.md, indícalo y propón también el cambio en CLAUDE.md.