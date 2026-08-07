# Plataforma unificada de gestión de proyectos

Consolidación de tres aplicaciones independientes en un único sistema, para
eliminar logins fragmentados y habilitar vínculos cruzados entre tareas,
bugs y componentes de software (con fines de métricas y reportes).

## Origen del proyecto

Este proyecto nace de la fusión de tres apps que antes vivían por separado:

| App original                            | Función                                                                                             | Stack original                              |
| --------------------------------------- | --------------------------------------------------------------------------------------------------- | ------------------------------------------- |
| **SimpleTask Engine (STE)**             | Gestión de proyectos estilo Kanban (tableros, listas, tareas, subtareas, dependencias entre tareas) | Base del proyecto actual                    |
| **Circular Docs**                       | Gestión de componentes de software y sus dependencias                                               | Express + React/Vite + SQLite               |
| **Use Cases – Manual Testing Flow App** | Gestión de casos de prueba manuales (QA)                                                            | SvelteKit + Tailwind + Drizzle ORM + SQLite |

**Motivación:** antes había que iniciar sesión en tres apps distintas para
ver información relacionada (por ejemplo, qué tarea está resolviendo un
bug, o en qué módulo se está trabajando). Unificar todo permite:

- Un solo login para todo el equipo.
- Vincular tareas con bugs y con componentes/módulos del sistema.
- Sacar métricas y estadísticas cruzando los tres dominios (Kanban, QA,
  Componentes).

## Stack

- **Backend:** Laravel (API)
- **Frontend:** SvelteKit + TailwindCSS
- **Base de datos:** PostgreSQL (infraestructura administrada por separado,
  fuera de este `docker-compose.yml`)
- **Contenedores:** Docker / Docker Compose

## Arquitectura de dominios

```
Identidad (users, extension_tokens)
   │
   ├── Kanban (boards, lists, tasks, subtasks, task_dependencies)
   │        │
   │        ├── task_component  ──┐
   │        └── task_bug       ──┼── vínculos cruzados
   │                              │
   ├── Componentes (components, component_dependencies) ◄┘
   │        │
   │        └── module_id (FK)
   │
   └── QA / Testing (test_cases, test_steps, test_case_actors, bugs, recordings)
```

- `components` unifica lo que antes eran `components` (Circular Docs) y
  `modules` (Use Cases), con jerarquía propia y dependencias con nivel de
  criticidad (`critical` / `optional`) — el mismo patrón que ya usaban las
  dependencias entre tareas de STE.
- `task_component` y `task_bug` son tablas puente que permiten asignar una
  tarea a un componente/módulo o a un bug específico.
- `comments`, `audits` y `recordings` son polimórficas y se pueden asociar
  a tareas, bugs, casos de prueba o componentes indistintamente.

## Estructura de servicios (Docker)

| Servicio       | Descripción                                                                 | Puerto  |
| -------------- | --------------------------------------------------------------------------- | ------- |
| `back-task`    | PHP-FPM con la app Laravel                                                  | interno |
| `back-nginx`   | Nginx sirviendo la API                                                      | `18080` |
| `front-dev`    | SvelteKit en modo desarrollo (Vite)                                         | `5173`  |
| `front-prod`   | SvelteKit compilado para producción                                         | `4173`  |
| `backend-init` | Job de una sola corrida: instala dependencias de Composer y ajusta permisos | —       |

La base de datos PostgreSQL se administra en infraestructura aparte y no
forma parte de este `docker-compose.yml`.

## Instalación

### Requisitos previos

- Docker y Docker Compose instalados
- `make`
- Acceso a la instancia de PostgreSQL (host, puerto, usuario, base y
  contraseña) ya provisionada

### Pasos

1. Cloná el repositorio y entrá a la carpeta del proyecto.

2. Copiá el archivo de variables de entorno y completalo con los datos de
   tu Postgres externo y demás configuración necesaria:

   ```bash
   cp .env.example .env
   ```

3. Construí las imágenes:

   ```bash
   make build
   ```

4. Instalá las dependencias de Composer y ajustá permisos (corre el
   servicio `backend-init` una sola vez):

   ```bash
   make init
   ```

5. Levantá todos los servicios:

   ```bash
   make up
   ```

6. Corré las migraciones contra la base Postgres externa:

   ```bash
   make migrate
   ```

7. La API queda disponible en `http://localhost:18080/api` y el frontend
   de desarrollo en `http://localhost:5173`.

## Uso del Makefile

| Comando                                      | Qué hace                                                                           |
| -------------------------------------------- | ---------------------------------------------------------------------------------- |
| `make build`                                 | Construye todas las imágenes de los servicios                                      |
| `make up`                                    | Levanta todos los contenedores en segundo plano                                    |
| `make down`                                  | Detiene y elimina los contenedores                                                 |
| `make restart`                               | Equivale a `down` seguido de `up`                                                  |
| `make logs`                                  | Sigue los logs de todos los servicios (últimas 150 líneas)                         |
| `make nginx-logs`                            | Sigue los logs solo de `back-nginx`                                                |
| `make ps`                                    | Lista el estado de los contenedores                                                |
| `make shell`                                 | Abre una shell dentro del contenedor `back-task`                                   |
| `make composer CMD="require paquete/nombre"` | Corre un comando de Composer dentro de `back-task`                                 |
| `make artisan CMD="make:model Bug -m"`       | Corre un comando de `php artisan` dentro de `back-task`                            |
| `make migrate`                               | Corre `php artisan migrate --force`                                                |
| `make migrate-fresh`                         | Corre `php artisan migrate:fresh --force` (borra y recrea todo — usar con cuidado) |
| `make init`                                  | Corre el servicio `backend-init` (composer install + permisos)                     |
| `make front-shell`                           | Abre una shell dentro del contenedor `front-dev`                                   |
| `make front-install`                         | Corre `pnpm install` dentro de `front-dev`                                         |
| `make front-build`                           | Reconstruye la imagen de `front-prod`                                              |

### Ejemplos

```bash
# Crear un nuevo modelo con su migración
make artisan CMD="make:model Component -m"

# Instalar una dependencia de PHP
make composer CMD="require spatie/laravel-activitylog"

# Ver el estado de los contenedores
make ps
```

## Migraciones

Las migraciones específicas del dominio unificado (`components`,
`component_dependencies`, `test_cases`, `test_steps`, `test_case_actors`,
`bugs`, `recordings`, `extension_tokens`, `comments`, `audits`,
`task_component`, `task_bug`, y el alter de `tasks` para sumar
`component_id`/`assigned_to`) viven en `database/migrations/` con prefijo
de fecha `2025_06_01_*`, pensadas para correr **después** de las
migraciones originales de STE (`users`, `boards`, `lists`, `tasks`,
`subtasks`, `task_dependencies`).

Si tus migraciones de STE tienen timestamps posteriores a esa fecha,
renombrá los prefijos para que el orden de ejecución sea correcto.
