---
name: sdd-clarify
description: SDD · Revisa la spec como un QA (solo detecta, no resuelve) y clasifica cada problema por gravedad
argument-hint: 002-nombre
allowed-tools: Read, Grep, Glob
---
Revisa specs/$1/spec.md como si fueras un QA muy profesional. Usa la skill sdd.

Busca:
1. Ambigüedades restantes (requisitos que no se pueden verificar).
2. Contradicciones entre requisitos.
3. Casos límite no cubiertos, incluido qué pasa con cada tipo de usuario (sin sesión, registrado, admin).
4. Conflictos con docs/constitution.md o con las reglas de dominio de CLAUDE.md.

Clasifica cada problema por gravedad:
- **BLOQUEANTE**: impide implementar o verificar un RF, o contradice docs/constitution.md o CLAUDE.md.
- **IMPORTANTE**: puede llevar a un comportamiento distinto del esperado o dejar un caso límite relevante sin cubrir.
- **MENOR**: redacción, precisión o detalles que no cambian lo que se implementa.

Formato: tres listas numeradas (BLOQUEANTE, IMPORTANTE, MENOR), en ese orden. En cada problema indica el tipo (ambigüedad, contradicción, caso límite o conflicto) y el RF o la sección afectada. Si dos problemas son el mismo, júntalos en uno.

No propongas soluciones todavía: solo detecta.
No modifiques ningún archivo.
