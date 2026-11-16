---
name: sdd-implement
description: SDD · Implementa UNA tarea, tests primero
argument-hint: 002-nombre T3
allowed-tools: Read, Grep, Glob, Edit, Write, Bash(php artisan test:*)
---
Implementa SOLO la tarea $2 de specs/$1/tasks.md, siguiendo specs/$1/plan.md, docs/constitution.md, CLAUDE.md y la skill sdd.

1. Escribe primero los tests en tests/Feature/ y comprueba que fallan (salvo en cambios puramente visuales, que no llevan test).
2. Escribe el código hasta que pasen. Crea solo los archivos que lista el plan.
3. Ejecuta php artisan test y muéstrame el resultado.
4. Revisa el checklist de Postgres y migraciones de la skill sdd e indica qué has comprobado.
5. Marca $2 como hecha en tasks.md e indica qué RF cubre.

Después PÁRATE. No empieces la siguiente tarea.
