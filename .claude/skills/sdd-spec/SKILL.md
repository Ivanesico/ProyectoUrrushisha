---
name: sdd-spec
description: SDD · Entrevista y genera la spec de una funcionalidad
argument-hint: 002-nombre idea inicial
allowed-tools: Read, Grep, Glob, Write
---
NO escribas código en ningún momento. Lee docs/constitution.md, CLAUDE.md y MEMORY.md, y usa la skill sdd.

Carpeta de la spec: specs/$1/
Idea inicial (todo lo que va después del nombre de la carpeta): $ARGUMENTS

Si specs/$1/spec.md ya existe, léela y trabaja sobre ella en vez de empezar de cero.

Tu trabajo:
1. Hazme preguntas de UNA en UNA para eliminar ambigüedades (casos límite, errores, qué ve cada tipo de usuario, qué queda fuera de esta versión). Máximo 5 preguntas.
2. Con mis respuestas, genera specs/$1/spec.md siguiendo la plantilla de la skill sdd (plantillas/spec.md), con los requisitos en EARS y "Estado: borrador".
3. Solo el QUÉ y el POR QUÉ: nada de stack, arquitectura ni nombres de archivos.
4. Al terminar, resume la spec en pocas líneas y para: espera mi aprobación.
