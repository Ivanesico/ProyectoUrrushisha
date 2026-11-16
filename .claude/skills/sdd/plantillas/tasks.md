# Plantilla de tareas (tasks.md)

```
- [ ] **Tn. <Descripción>.** RF-x, RF-y
  - Hecho cuando: <comprobación verificable>.
```

- Máximo 5 tareas, en orden de dependencia.
- Tareas grandes y coherentes: cada una deja el proyecto funcionando y con `php artisan test` en verde.
- Agrupa por capa o bloque lógico (por ejemplo: migración y modelo; controlador y validación con sus tests; vistas), no por archivo ni por función.
- La verificación en el navegador no es una tarea: la hace el reviewer en la validación.
- Si no caben en 5 tareas, propón dividir la spec.
