# UrruShisha

UrruShisha es una aplicación web desarrollada con Laravel para gestionar un catálogo de sabores de shisha/cachimba. El proyecto permite a los usuarios consultar sabores, filtrarlos, guardarlos como favoritos y crear mezclas personalizadas con diferentes proporciones.

## Descripción del proyecto

El objetivo de este proyecto es crear una plataforma sencilla e intuitiva donde los usuarios puedan organizar sabores de shisha, consultar información sobre cada uno de ellos y guardar sus combinaciones favoritas.

La aplicación incluye autenticación de usuarios, gestión de sabores, marcas y categorías, sistema de favoritos, creación de mezclas personalizadas y un panel de administración para gestionar la información principal del catálogo.

Demo: <https://proyectourrushisha-production.up.railway.app/>

## Funcionalidades principales

* Registro e inicio de sesión de usuarios.
* Catálogo de sabores consultable sin iniciar sesión, con buscador.
* Filtro de sabores por marca, categoría, ingredientes, tipo de tabaco y valoración.
* Ordenación del catálogo, incluida la ordenación por precio (formato de 50 g).
* Precios por formato y valoración de sabores de 1 a 5 estrellas.
* Sistema de favoritos para guardar sabores.
* Creación, edición y eliminación de mezclas personalizadas, con el reparto de porcentajes ajustable mediante tiradores.
* Mezclas públicas que otros usuarios pueden consultar y guardar como copia propia.
* Panel de administración para gestionar sabores, marcas, categorías y tipos de tabaco.
* Rutas protegidas mediante autenticación y control de acceso para administradores.

## Tecnologías utilizadas

* Laravel 12 y PHP 8.2
* Blade
* Bootstrap 5.3 (por CDN, sin paso de build)
* PostgreSQL en producción y MySQL (XAMPP) en local
* Railway (despliegue continuo desde GitHub)
* PHPUnit (tests de funcionalidad)
* Git y GitHub

## Desarrollo con agentes de IA (Spec-Driven Development)

La primera versión de UrruShisha la desarrollé a mano. A partir de ella, el resto de funcionalidades las he desarrollado con agentes de IA (Claude Code) siguiendo un flujo de **Spec-Driven Development** que he diseñado para este proyecto. Los agentes escriben y prueban el código, y yo defino los requisitos, tomo las decisiones y apruebo cada fase antes de seguir.

**El flujo** (`.claude/skills/sdd-flow/`):

1. **Spec o plan:** un agente *planner* convierte la petición en requisitos verificables (RF). Si algo es ambiguo, no lo supone: me pregunta.
2. **Aprobación:** nada se implementa hasta que apruebo el plan.
3. **Implementación:** un agente *implementer* nuevo por tarea, con los tests primero (en rojo y luego en verde).
4. **Validación:** un agente *reviewer* comprueba cada RF con los tests y en el navegador (Chrome DevTools, también a 375 px) y emite un veredicto.
5. **Cierre:** commit, y el push (que despliega en producción) solo con mi confirmación.

**Dónde verlo en el repositorio:**

* [`CLAUDE.md`](CLAUDE.md): reglas del proyecto para los agentes (stack, reglas de dominio, trampas conocidas como las diferencias entre Postgres y MySQL).
* [`docs/constitution.md`](docs/constitution.md): principios innegociables del proyecto.
* [`.claude/agents/`](.claude/agents/): los agentes planner, implementer y reviewer.
* [`.claude/skills/`](.claude/skills/): las fases del flujo SDD y skills de apoyo, como la que consulta los precios oficiales del tabaco en el BOE.
* [`specs/`](specs/): las specs y planes de cada funcionalidad. Un ejemplo completo: [`specs/015-ordenar-por-precio/plan.md`](specs/015-ordenar-por-precio/plan.md).
* [`MEMORY.md`](MEMORY.md): la memoria del proyecto entre sesiones (estado, decisiones y errores a evitar).

## Estructura principal del proyecto

El proyecto sigue una estructura basada en Laravel y el patrón MVC:

* `app/Models`: modelos principales de la aplicación, como usuarios, sabores, marcas, categorías, favoritos y mezclas.
* `app/Http/Controllers`: controladores encargados de gestionar la lógica de la aplicación.
* `resources/views`: vistas Blade utilizadas para mostrar la interfaz.
* `routes/web.php`: definición de rutas web.
* `database/migrations`: migraciones de la base de datos.
* `public`: archivos públicos de la aplicación.

## Base de datos

La aplicación trabaja con varias entidades relacionadas entre sí:

* Usuarios
* Marcas
* Categorías
* Sabores
* Favoritos
* Mezclas
* Sabores incluidos en cada mezcla

Estas relaciones permiten que cada usuario pueda guardar sus sabores favoritos y crear sus propias mezclas personalizadas.

## Instalación y ejecución

1. Clonar el repositorio:

```bash
git clone https://github.com/Ivanesico/ProyectoUrrushisha.git
```

2. Entrar en la carpeta del proyecto:

```bash
cd ProyectoUrrushisha
```

3. Instalar dependencias de PHP:

```bash
composer install
```

4. Copiar el archivo de entorno:

```bash
cp .env.example .env
```

5. Generar la clave de la aplicación:

```bash
php artisan key:generate
```

6. Configurar la conexión a la base de datos en el archivo `.env`.

7. Ejecutar las migraciones:

```bash
php artisan migrate
```

8. (Opcional, solo en local) Crear usuarios de prueba:

```bash
php artisan db:seed --class=LocalTestUsersSeeder
```

9. Levantar el servidor de Laravel:

```bash
php artisan serve
```

10. Ejecutar los tests:

```bash
php artisan test
```

## Aprendizajes del proyecto

Durante el desarrollo de UrruShisha he trabajado conceptos importantes del desarrollo web con Laravel, como:

* Organización de un proyecto siguiendo el patrón MVC.
* Creación de rutas, controladores, modelos y vistas.
* Gestión de usuarios y autenticación.
* Operaciones CRUD.
* Relaciones entre tablas.
* Uso de Blade para crear vistas dinámicas.
* Validación de formularios.
* Protección de rutas mediante middleware.
* Gestión de una base de datos relacional.
* Uso de GitHub para control de versiones.
* Despliegue continuo en Railway con PostgreSQL, manteniendo la compatibilidad con MySQL en local.
* Desarrollo guiado por especificaciones (SDD) con agentes de IA: definir requisitos verificables, revisar planes, exigir tests y validar cada entrega antes de desplegar.

## Autor

Proyecto desarrollado por Iván Escobar Sánchez como aplicación web personal para practicar y mejorar conocimientos en desarrollo web con Laravel.

