# Plantilla de plan (plan.md)

El plan debe incluir, en este orden:

1. Archivos que se crean o modifican y la responsabilidad de cada uno. Marca aparte los archivos nuevos.
2. Rutas: nuevas o modificadas, con su método, su nombre y su middleware (`guest`, `auth`, `admin`).
3. Base de datos: migraciones nuevas, cómo afectan a los datos que ya hay en producción y cambios en los modelos (`fillable`, `casts`, relaciones).
4. Lógica: controladores y Form Requests (reglas de validación y mensajes en español).
5. Interfaz: vistas Blade y componentes de Bootstrap; cómo se ve en móvil.
6. Permisos: qué puede hacer cada tipo de usuario (sin sesión, registrado, admin).
7. Decisiones técnicas justificadas, cada una con su alternativa descartada.
8. Estrategia de tests con `php artisan test`: qué tests de `tests/Feature/` se crean y qué RF cubre cada uno.

Indica qué RF cubre cada parte. Todos los RF de la spec deben quedar cubiertos.
Revisa el checklist de Postgres y migraciones de la skill sdd.
