---
name: sdd-plan
description: SDD · Genera el plan técnico de una spec aprobada
argument-hint: 002-nombre
allowed-tools: Read, Grep, Glob, Write
---
Lee docs/constitution.md, CLAUDE.md, MEMORY.md y specs/$1/spec.md. Usa la skill sdd.
NO escribas código.

Si la spec no está aprobada o tiene dudas abiertas, para y avísame.

Genera specs/$1/plan.md siguiendo la plantilla de la skill sdd (plantillas/plan.md): archivos que se crean o modifican, rutas, base de datos, lógica, interfaz, permisos por tipo de usuario, decisiones técnicas justificadas (con su alternativa descartada) y estrategia de tests con php artisan test.

Todo debe respetar la constitución y CLAUDE.md, y cubrir todos los RF. Marca qué RF cubre cada parte.

Al terminar, espera mi aprobación.
