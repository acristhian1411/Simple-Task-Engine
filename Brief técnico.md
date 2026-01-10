# Brief de Proyecto: SimpleTask Engine

## 1. Descripción del Proyecto

**SimpleTask Engine** es una aplicación de gestión de proyectos basada en la metodología Kanban (estilo Trello). El objetivo es ofrecer una herramienta de organización visual donde los usuarios puedan gestionar flujos de trabajo a través de tableros, listas y tarjetas.

A diferencia de un clon estándar, este proyecto introduce **lógica de control jerárquico**:

* **Subtareas:** Para desglosar unidades de trabajo mínimas dentro de una tarea.
* **Dependencias de Tareas:** Una funcionalidad crítica que permite bloquear el progreso de una tarea (ej. "Frontend") hasta que su predecesora (ej. "Backend") haya sido marcada como completada.

El enfoque principal es la **simplicidad técnica** y la **separación de responsabilidades**, utilizando un stack moderno, reactivo y altamente escalable mediante contenedores.

---

## 2. Arquitectura del Sistema

El sistema se dividirá en dos repositorios independientes que se comunican mediante una API RESTful.

* **Frontend:** SPA (Single Page Application) construida con **Svelte**.
* **Backend:** API robusta construida con **Laravel 11**.
* **Base de Datos:** **PostgreSQL** para manejo de relaciones complejas.
* **Infraestructura:** Entornos aislados mediante **Docker** y **Docker Compose**.

---

## 3. Modelo de Datos (PostgreSQL)

Para manejar subtareas y dependencias de forma eficiente sin complicar la lógica, utilizaremos una estructura de tablas relacionales:

### Entidades Principales:

* **Boards:** `id, title, description, user_id`.
* **Lists:** `id, board_id, title, order`.
* **Tasks:** `id, list_id, title, description, status, order`.
* **Subtasks:** `id, task_id, title, is_completed`. (Relación 1:N con Task).
* **Task_Dependencies:** `id, task_id, depends_on_task_id`. (Relación N:N que vincula una tarea con sus bloqueadores).

---

## 4. Stack Tecnológico

### Backend (Laravel API)

* **Autenticación:** Laravel Sanctum (simple, basado en tokens).
* **Validación:** Form Requests para asegurar que no se creen dependencias circulares.
* **Recursos:** API Resources para entregar JSON limpio al frontend.
* **Lógica de Dependencias:** El backend rechazará intentos de finalizar tareas si sus dependencias registradas no están en estado "Done".

### Frontend (Svelte)

* **Estado:** Svelte Stores para gestionar la reactividad de los tableros.
* **Drag & Drop:** `svelte-dnd-action` para una experiencia de usuario fluida.
* **Estilos:** Tailwind CSS para un diseño limpio y rápido de implementar.
* **Comunicación:** Axios o Fetch API para interactuar con la API de Laravel.

---

## 5. Estrategia de Docker

Se utilizará un archivo `docker-compose.yml` para orquestar tres servicios principales en desarrollo y producción:

1. **Backend (PHP-FPM + Nginx):** Contenedor con PHP 8.3 optimizado para Laravel.
2. **Frontend (Node.js):** Contenedor para el servidor de desarrollo Vite y la generación del build.
3. **Database (PostgreSQL 16):** Persistencia de datos con volúmenes locales.

---

## 6. Funcionalidades Clave y Lógica de Negocio

### Gestión de Dependencias (El "Core")

Para implementar el caso de uso donde el Frontend depende del Backend:

1. **Interfaz:** En el detalle de la tarea "Frontend", el usuario selecciona "Backend" como dependencia.
2. **Restricción:** Si el usuario intenta arrastrar "Frontend" a la columna "Completado", el frontend (apoyado por el backend) mostrará una alerta indicando que la tarea "Backend" debe finalizarse primero.

### Subtareas

* Listado simple de checklist dentro de cada tarjeta.
* **Progreso Visual:** Una barra de porcentaje o un contador (ej. 2/5) que indica cuántas subtareas se han completado sin necesidad de abrir la tarea.

---

## 7. Próximos Pasos Sugeridos

1. **Definición de Endpoints:** Diseñar el CRUD de Boards, Lists y la lógica de vinculación de dependencias.
2. **Setup de Docker:** Configurar el entorno para que ambos equipos (front y back) puedan levantar el proyecto con un solo comando.
3. **MVP UI:** Crear el layout de columnas en Svelte para visualizar el flujo básico de arrastrar y soltar.

