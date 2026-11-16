---
name: sdd-validate
description: SDD · Valida la spec RF por RF (tests + Chrome DevTools)
argument-hint: 002-nombre
allowed-tools: Read, Grep, Glob, Bash(php artisan test:*), Bash(git diff:*), Bash(git status:*), mcp__chrome-devtools
---
Usa la skill sdd.

Recorre specs/$1/spec.md (o plan.md en modo ligero) requisito por requisito. Para cada RF indica qué test lo cubre y el resultado de ejecutarlo con php artisan test.

Los RF de interfaz que no se puedan testear, verifícalos con el MCP de Chrome DevTools en http://localhost:8000 (incluida la vista móvil de 375 px y la consola sin errores). Para iniciar sesión usa los usuarios de prueba de database/seeders/LocalTestUsersSeeder.php. Si el servidor local no responde, para y avísame: no lo arranques tú.

Revisa el diff completo (git diff) con el checklist de Postgres y migraciones de la skill sdd, porque los tests corren en SQLite y no lo detectan.

Si algún RF no está cubierto o falla, dilo claramente. NO arregles nada todavía.

Después comprueba los criterios de finalización y dame un veredicto: APROBADO o CAMBIOS NECESARIOS (con la lista exacta de lo que falla).
