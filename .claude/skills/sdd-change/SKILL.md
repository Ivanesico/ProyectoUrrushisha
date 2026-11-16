---
name: sdd-change
description: SDD · Nuevo requisito - primero la spec, luego el código
argument-hint: 002-nombre descripción del cambio
allowed-tools: Read, Grep, Glob, Edit
---
Nuevo requisito para la spec specs/$1/ (todo lo que va después del nombre de la carpeta): $ARGUMENTS

NO toques código. Usa la skill sdd.

1. Propón el cambio en specs/$1/spec.md (o en plan.md si es una spec en modo ligero): nuevo RF (o cambio de uno existente) en EARS, sus casos límite y lo que queda fuera de alcance.
2. Indica qué partes de plan.md y tasks.md habría que cambiar después.
3. Muéstrame el diff y espera mi aprobación. Solo cuando la dé, aplica el cambio.
