# UrruShisha

UrruShisha es una aplicación web desarrollada con Laravel para gestionar un catálogo de sabores de shisha/cachimba. El proyecto permite a los usuarios consultar sabores, filtrarlos, guardarlos como favoritos y crear mezclas personalizadas con diferentes proporciones.

## Descripción del proyecto

El objetivo de este proyecto es crear una plataforma sencilla e intuitiva donde los usuarios puedan organizar sabores de shisha, consultar información sobre cada uno de ellos y guardar sus combinaciones favoritas.

La aplicación incluye autenticación de usuarios, gestión de sabores, marcas y categorías, sistema de favoritos, creación de mezclas personalizadas y un panel de administración para gestionar la información principal del catálogo.

## Funcionalidades principales

* Registro e inicio de sesión de usuarios.
* Consulta de catálogo de sabores.
* Filtro de sabores por marca, categoría, ingredientes y tipo de tabaco.
* Sistema de favoritos para guardar sabores.
* Creación, edición y eliminación de mezclas personalizadas.
* Asignación de proporciones a los sabores dentro de una mezcla.
* Gestión de marcas.
* Gestión de categorías.
* Panel de administración para gestionar sabores, marcas y categorías.
* Rutas protegidas mediante autenticación.
* Control de acceso para usuarios administradores.

## Tecnologías utilizadas

* Laravel
* PHP
* Blade
* MySQL
* Tailwind CSS
* Vite
* HTML
* CSS
* GitHub

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

4. Instalar dependencias de Node:

```bash
npm install
```

5. Copiar el archivo de entorno:

```bash
cp .env.example .env
```

6. Generar la clave de la aplicación:

```bash
php artisan key:generate
```

7. Configurar la conexión a la base de datos en el archivo `.env`.

8. Ejecutar las migraciones:

```bash
php artisan migrate
```

9. Compilar los assets:

```bash
npm run dev
```

10. Levantar el servidor de Laravel:

```bash
php artisan serve
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

## Autor

Proyecto desarrollado por Iván Escobar Sánchez como aplicación web personal para practicar y mejorar conocimientos en desarrollo web con Laravel.

