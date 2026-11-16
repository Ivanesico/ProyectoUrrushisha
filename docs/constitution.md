# Constitución — UrruShisha

Principios innegociables. Toda spec, plan y tarea debe cumplirlos.

1. **Simplicidad primero**: Laravel, Blade y Bootstrap por CDN. Sin dependencias nuevas (Composer ni npm) ni paso de build.
2. **La spec manda**: nada se implementa si no está en la spec activa (en modo ligero, el plan breve hace de spec). Si falta una decisión, se para y se pregunta.
3. **Lógica fuera de las vistas**: la validación va en Form Requests y la lógica en controladores y modelos. Las vistas Blade solo muestran datos: sin consultas ni reglas de negocio.
4. **Tests como puerta**: la lógica se prueba con `php artisan test` (tests de Feature con `RefreshDatabase`). Prohibido avanzar con tests en rojo.
5. **Los datos de producción son sagrados**: todo debe funcionar en Postgres. Las migraciones existentes nunca se modifican; las nuevas deben ser compatibles con los datos que ya hay. Nunca se pierden sabores, favoritos ni mezclas.
6. **Permisos en el servidor**: cada usuario solo ve y modifica lo suyo, y los permisos de admin se comprueban en el servidor (middleware o Form Request), nunca solo ocultando botones.
7. **Idioma**: código en inglés; interfaz y documentación en español.