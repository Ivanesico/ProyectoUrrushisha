---
name: sdd-tasks
description: SDD · Divide el plan en tareas grandes y verificables
argument-hint: 002-nombre
allowed-tools: Read, Grep, Glob, Write
---
Usa la skill sdd. NO escribas código.

A partir de specs/$1/spec.md y specs/$1/plan.md, genera specs/$1/tasks.md siguiendo la plantilla de la skill sdd (plantillas/tasks.md):
- Máximo 5 tareas grandes y coherentes, en orden de dependencia.
- Cada una con los RF que cubre y una línea "Hecho cuando:" verificable.
- Checkboxes.
- Si no caben en 5, propón dividir la spec.

Al terminar, espera mi aprobación.
