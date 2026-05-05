# SProjects — Project Management App (JIRA-like)

## Estado actual del proyecto (última actualización: 2026-05-05)

### Completado ✅
- Instalación completa: Laravel 12 + Filament 3.3 + Spatie Permissions + Sanctum
- Base de datos: 14 migraciones ejecutadas, seeders funcionando
- **Panel unificado `/app`**: usuarios normales ven 5 páginas de trabajo; admins ven además sección "Administración"
- Páginas de trabajo (todos los usuarios): ProjectList, KanbanBoard, ScrumBoard, GanttView, WaterfallView
- Sección Administración (solo admin/super_admin): UserResource, ProjectResource, TaskResource, SprintResource, MilestoneResource, ImputationResource
- Panel `/admin` eliminado — login único en `http://sprojects.test:8888/app/login`
- Control de acceso por recurso: `canViewAny()` (403 si acceso directo por URL) + `shouldRegisterNavigation()` (oculta nav)
- Drag & drop: SortableJS integrado en Kanban y Scrum
- Gantt: Frappe Gantt integrado (usando `dist/frappe-gantt.js`)
- REST API: 29 endpoints bajo `/api/v1/` con autenticación Sanctum
- Roles y permisos: 6 roles, 22 permisos, seeders con datos demo
- Virtual host MAMP: `sprojects.test:8888` apuntando a `public/`
- Header negro: `.fi-sidebar-header` (sidebar) y `.fi-topbar > nav` (topbar) con texto blanco
- Avatar usuario: `filter: invert(1)` → fondo blanco, texto negro
- `database/sprojects.sql` incluido en el repo (dump actualizado en cada commit)

### Pendiente / Por verificar 🔲
- Confirmar visualmente que el header negro funciona correctamente (sidebar + topbar + avatar)
- Verificar que `dev@sprojects.test` NO ve la sección Administración en el sidebar
- Verificar que `admin@sprojects.test` SÍ ve la sección Administración
- Probar drag & drop en Kanban y Scrum en el navegador
- Probar vista Gantt con tareas con fechas
- Probar vista Waterfall
- Probar REST API con cliente HTTP (login → token → GET /projects)

### Arquitectura de paneles
- **Un solo panel**: `/app` para todos los usuarios
- Recursos admin en `app/Filament/App/Resources/` con `canViewAny()` + `shouldRegisterNavigation()` que comprueban rol `admin`/`super_admin`
- Páginas de trabajo en `app/Filament/App/Pages/`
- `AdminPanelProvider` eliminado; `app/Filament/Admin/` eliminado

### Decisiones de diseño
- Header negro para todos los usuarios (sidebar + topbar) — diferencia visual respecto a un admin panel estándar Indigo
- Sección "Administración" agrupada al final del sidebar, solo visible para admin/super_admin
- Avatar usuario: fondo blanco, texto negro (`filter: invert(1)` sobre imagen de ui-avatars.com)

### Workflow de commits
Antes de cada commit: (1) dump MySQL → `database/sprojects.sql`, (2) actualizar este archivo SPROJECTS.md

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP 8.2) |
| Admin Panel | Filament 3.3 |
| Permissions | Spatie Laravel Permission 6.25 |
| API Auth | Laravel Sanctum 4.3 |
| Database | MySQL via MAMP (port 8889) |
| Frontend build | Vite 7 + Alpine.js |
| Drag & Drop | SortableJS |
| Gantt | Frappe Gantt (dist build: `frappe-gantt/dist/frappe-gantt.js`) |
| Livewire | Livewire 3 (included with Filament) |

---

## URLs & Access

| What | URL |
|---|---|
| App panel (users) | http://sprojects.test:8888/app |
| Admin panel | http://sprojects.test:8888/admin |
| API base | http://sprojects.test:8888/api/v1/ |

### Virtual Host (MAMP)
File: `/Applications/MAMP/conf/apache/extra/httpd-vhosts.conf`
- `sprojects.test:8888` → `/Applications/MAMP/htdocs/ClaudeCodePrj/sprojects/public`
- `/etc/hosts` entry: `127.0.0.1 sprojects.test`

### .env Key Values
```
APP_URL=http://sprojects.test:8888
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=sprojects
DB_USERNAME=root
DB_PASSWORD=root
CACHE_STORE=file
```

---

## Demo Users

| Email | Password | Role |
|---|---|---|
| admin@sprojects.test | password | super_admin |
| pm@sprojects.test | password | project_manager |
| dev@sprojects.test | password | developer |

---

## Roles & Permissions (Spatie)

### Roles
- `super_admin` — Full access
- `admin` — User and project management
- `project_manager` — Create and manage own projects
- `developer` — Work on assigned tasks, log hours
- `observer` — Read-only on assigned projects
- `client` — View project status and milestones

### Permissions (22 total)
```
projects.create / view / update / delete / archive
tasks.create / view / update / delete / assign
users.invite / manage / view
imputations.create / view / manage
sprints.create / manage
milestones.manage
reports.view
settings.manage
```

---

## Database Schema

### Tables (14 migrations)
1. `users` — + `avatar`, `position`, `timezone` fields
2. `projects` — `name`, `slug`, `description`, `methodology` (scrum|kanban|waterfall), `status` (active|archived|completed), `owner_id`, `start_date`, `end_date`, `color`, `cover_image`
3. `project_members` (pivot) — `project_id`, `user_id`, `role` (manager|developer|observer|client)
4. `task_statuses` — `project_id`, `name`, `color`, `order`, `wip_limit`, `is_default`, `is_done`
5. `milestones` — `project_id`, `name`, `description`, `due_date`, `status` (pending|in_progress|completed), `color`
6. `sprints` — `project_id`, `name`, `goal`, `status` (planning|active|completed), `start_date`, `end_date`
7. `tasks` — `project_id`, `parent_id` (nullable, self-ref for subtasks), `task_status_id`, `sprint_id`, `milestone_id`, `assigned_to`, `created_by`, `title`, `description`, `priority` (low|medium|high|urgent), `type` (task|bug|story|epic), `story_points`, `estimated_hours`, `due_date`, `position`
8. `task_imputations` — `task_id`, `user_id`, `hours`, `date`, `description`
9. `task_comments` — `task_id`, `user_id`, `body`
10. `task_attachments` — `task_id`, `user_id`, `filename`, `path`, `size`, `mime_type`
11. `sessions`, `cache`, `jobs`, `personal_access_tokens`, `permission_tables` (framework)

---

## File Structure

```
sprojects/
├── app/
│   ├── Filament/
│   │   ├── Admin/Resources/        # Panel /admin
│   │   │   ├── UserResource.php
│   │   │   ├── ProjectResource.php
│   │   │   ├── TaskResource.php
│   │   │   ├── MilestoneResource.php
│   │   │   ├── SprintResource.php
│   │   │   └── ImputationResource.php
│   │   └── App/Pages/              # Panel /app (daily work)
│   │       ├── ProjectList.php
│   │       ├── KanbanBoard.php      # SortableJS drag & drop
│   │       ├── ScrumBoard.php       # SortableJS + backlog
│   │       ├── GanttView.php        # Frappe Gantt
│   │       └── WaterfallView.php
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── ProjectController.php
│   │   ├── TaskController.php
│   │   ├── MilestoneController.php
│   │   ├── SprintController.php
│   │   └── ImputationController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── ProjectMember.php
│   │   ├── Task.php                 # self-referential (parent_id for subtasks)
│   │   ├── TaskStatus.php
│   │   ├── Sprint.php
│   │   ├── Milestone.php
│   │   ├── TaskImputation.php
│   │   ├── TaskComment.php
│   │   └── TaskAttachment.php
│   └── Providers/Filament/
│       ├── AdminPanelProvider.php   # /admin, Indigo, restricted to super_admin + admin
│       └── AppPanelProvider.php     # /app, Violet, open to all roles
├── database/
│   ├── migrations/                  # 14 migration files
│   └── seeders/
│       ├── RolesSeeder.php          # 22 permissions, 6 roles, creates admin user
│       └── DemoProjectSeeder.php    # Demo Kanban + Scrum projects with tasks
├── resources/
│   ├── js/
│   │   └── app.js                   # imports SortableJS + Frappe Gantt, binds to window
│   └── views/filament/app/pages/
│       ├── kanban-board.blade.php
│       ├── scrum-board.blade.php
│       ├── gantt-view.blade.php
│       └── waterfall-view.blade.php (if exists)
└── routes/
    ├── web.php
    └── api.php                      # 29 REST endpoints under /api/v1/
```

---

## Filament Panel Architecture

### Admin Panel (`/admin`)
- **Provider**: `app/Providers/Filament/AdminPanelProvider.php`
- **Color**: Indigo
- **Access**: `canAccessPanel()` restricted to `super_admin` and `admin` roles
- Discovers resources from `app/Filament/Admin/Resources/`

### App Panel (`/app`)
- **Provider**: `app/Providers/Filament/AppPanelProvider.php`
- **Color**: Violet
- **Access**: All authenticated users
- Discovers pages from `app/Filament/App/Pages/`
- **Black header styling**: CSS injected via `renderHook('panels::head.end')` targeting:
  - `.fi-sidebar-header` — sidebar brand area (black bg, white text)
  - `.fi-topbar > nav` — topbar (black bg)
  - `.fi-user-avatar` — user avatar (inverted with `filter: invert(1)` → white bg, black text)
- Frappe Gantt CSS loaded via `renderHook('panels::styles.before')`

---

## REST API Endpoints

All under `/api/v1/` with Sanctum auth middleware (except auth routes).

```
POST   /auth/login
POST   /auth/logout
GET    /auth/me

GET    /projects
POST   /projects
GET    /projects/{id}
PUT    /projects/{id}
DELETE /projects/{id}
GET    /projects/{id}/members
POST   /projects/{id}/members
DELETE /projects/{id}/members/{userId}

GET    /projects/{id}/tasks
POST   /projects/{id}/tasks
GET    /tasks/{id}
PUT    /tasks/{id}
DELETE /tasks/{id}
PATCH  /tasks/{id}/move
GET    /tasks/{id}/subtasks
POST   /tasks/{id}/subtasks

GET    /projects/{id}/milestones
POST   /projects/{id}/milestones
PUT    /milestones/{id}

GET    /projects/{id}/sprints
POST   /projects/{id}/sprints
PATCH  /sprints/{id}/start
PATCH  /sprints/{id}/complete

POST   /tasks/{id}/imputations
GET    /tasks/{id}/imputations
GET    /projects/{id}/imputations

GET    /users
```

---

## Key Technical Notes

### Livewire 3 Rules (Filament pages)
- Every Blade template must have a **single root element**
- `<style>` tags must use `@push('styles')` / `@endpush`
- JS must use `@script` / `@endscript` (Livewire 3 scoped directive)
- Use `$wire.call('method', args)` for Livewire method calls from JS
- Use `$wire.on('event', callback)` for listening to dispatched events

### Frappe Gantt Import
Import via `frappe-gantt/dist/frappe-gantt.js` (NOT the package default which points to SCSS source and requires `sass-embedded`).

### Asset Publishing
After `php artisan filament:assets` — publishes Filament static assets to `public/` (needed when running on non-root path).

### SortableJS Usage
SortableJS is bound to `window.Sortable` in `resources/js/app.js`. In Blade: `new Sortable(element, { group, animation, onEnd })`.

### Cache
Driver: `file`. Use `cache()->tags(['project-{id}'])` pattern for project-scoped cache.

---

## Common Commands

```bash
# Run from: /Applications/MAMP/htdocs/ClaudeCodePrj/sprojects

# Migrations + seed
php artisan migrate --seed

# Fresh seed (destructive)
php artisan migrate:fresh --seed

# Filament assets
php artisan filament:assets

# Build frontend
npm run build

# Dump DB before commit
/Applications/MAMP/Library/bin/mysqldump -u root -proot --port=8889 sprojects > database/sprojects.sql
```

---

## GitHub
- Repo: `https://github.com/Serchyperez/sproject.git`
- Remote: `origin`

---

## Known Issues / Decisions

| Issue | Resolution |
|---|---|
| Frappe Gantt SCSS build error | Use `dist/frappe-gantt.js` instead of package entry |
| Livewire JS 404 in subdirectory | Fixed by MAMP virtual host pointing to `public/` |
| Filament topbar selector `.fi-topbar > div` | Wrong — actual element is `<nav>`, use `.fi-topbar > nav` |
| Filament sidebar brand in `fi-sidebar-header` | Separate element from topbar; needs own CSS rule |
| Spatie permission `milestones.view` not found | Changed to `milestones.manage` in client role seeder |
| Laravel 12 API routes | Must explicitly register: `api: __DIR__.'/../routes/api.php'` in `bootstrap/app.php` |
