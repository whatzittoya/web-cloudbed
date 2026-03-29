---
name: replicate-slim-mvc-booking-app
description: Replicate the structure, UI patterns, and MVC conventions of the Quinos booking app into another PHP project. Use when Codex needs to clone or adapt this app's Slim 4, PHP-DI, Twig, Tailwind, jQuery, and PDO-based MVC setup; port screens such as login, dashboard, check-in/check-out, or reservations; or scaffold a new project that should feel and behave like this source app.
---

# Replicate Slim MVC Booking App

## Overview

Reuse this skill to copy the architectural shape of this app into another project without copying its domain rules blindly. Recreate the stack in the same order the source app uses it: bootstrap, container, middleware, routes, controllers, models, services, then Twig views.

Read `references/source-app-architecture.md` first to see the exact files and responsibilities in the source app.

## Replication Workflow

### 1. Define the replication target

Decide whether the destination needs one of these scopes:

- Full clone: recreate the full Slim MVC shell and all major screens.
- Shell clone: recreate bootstrap, layout, auth guard, and one starter module.
- UI-only clone: recreate Twig layout, Tailwind conventions, and JS behavior while changing the backend.
- MVC-only clone: recreate folder structure, DI, routes, controllers, models, and services without preserving the exact booking UI.

Keep the stack and boundaries stable even when the business domain changes.

### 2. Recreate the framework baseline

Install and wire the same core packages:

- `slim/slim`
- `slim/psr7`
- `php-di/slim-bridge`
- `slim/twig-view`
- `slim/flash`
- `guzzlehttp/guzzle`

Mirror the same top-level layout:

```text
config/
public/
src/
  Controllers/
  Middleware/
  Models/
  Services/
templates/
database/
bin/
```

Preserve these responsibilities:

- `public/`: bootstrap and web entrypoint.
- `config/`: DI definitions and route registration.
- `src/Controllers/`: HTTP orchestration only.
- `src/Models/`: PDO queries and persistence.
- `src/Services/`: external API or integration clients.
- `src/Middleware/`: cross-cutting request/session concerns.
- `templates/`: Twig layouts and page templates.
- `database/`: schema/bootstrap helpers.
- `bin/`: manual or scheduled sync scripts.

### 3. Copy the bootstrap pattern

Rebuild the entry flow to match the source app:

1. Load Composer autoloading.
2. Build the DI container from `config/container.php`.
3. Create the Slim app from the container.
4. Set `base_path` from configuration.
5. Add body parsing, routing, error handling, and session middleware.
6. Load routes from `config/routes.php`.
7. Run the app.

Keep `base_path` explicit. The source app depends on it for links and redirects.

### 4. Copy the DI and configuration pattern

Keep `config/container.php` as the single place that wires shared dependencies. Register these categories:

- `settings`: app name, base path, database settings, integration settings.
- `PDO::class`: database connection with error mode and fetch mode.
- HTTP clients: `GuzzleHttp\Client` configured for upstream APIs.
- Services: thin wrappers such as the booking API service.
- UI services: `Slim\Flash\Messages` and `Slim\Views\Twig`.

Apply these rules when adapting the pattern:

- Move secrets to environment variables even if the source app hardcodes them.
- Keep Twig globals for `app_name`, `base_path`, and flash messages.
- Keep one container definition per dependency to make constructor injection predictable.

### 5. Preserve the MVC boundaries

Keep the same separation of concerns:

- Routes map URLs to controller methods and do not contain business logic.
- Controllers validate request input, enforce session checks, call models or services, and return HTML, redirects, or JSON.
- Models accept `PDO` in the constructor and own SQL, joins, inserts, deletes, and result shaping.
- Services wrap external APIs and normalize success and error payloads.
- Templates render prepared view data and keep business logic minimal.

Do not move SQL into controllers or templates. Do not call external APIs directly from Twig or route files.

### 6. Recreate each vertical slice

Port features one slice at a time in this order:

1. Route definition
2. Controller
3. Model or service dependency
4. Twig view
5. Frontend behavior

Use this route-to-view pattern as the default:

```text
GET /page
  -> Controller@index
  -> Model queries and filters
  -> Twig render with view data

POST /action
  -> Controller@action
  -> Model or service mutation
  -> Redirect with flash message or JSON response
```

For JSON endpoints, keep the source style:

- Write JSON directly to the PSR-7 response body.
- Set `Content-Type: application/json`.
- Return explicit status codes for validation, auth, not-found, and upstream failures.

### 7. Preserve the UI system

Keep the design language coherent across all pages:

- Use a single base Twig layout.
- Load Tailwind from CDN for utility-first page styling.
- Use jQuery for page interactions already shaped around selectors and event handlers.
- Use DataTables for searchable and sortable admin tables.
- Use rounded cards, soft borders, gray surfaces, and blue as the primary action color.
- Use compact headings, muted helper text, and status pills for state.

Preserve these page-level conventions:

- Sticky top navigation with app name, active section link, user badge, and logout link.
- Flash success and error banners rendered in the base layout.
- Card-based forms with generous padding and rounded corners.
- Modal overlays for detail views and confirmations.
- Inline status chips for reservation states.
- Embedded page scripts in Twig when behavior is page-specific.

When cloning the visual design, copy the system rather than pixel-matching every page. Keep component shapes, spacing, color intent, and interaction style consistent.

### 8. Preserve auth and session behavior

Keep the source auth flow simple unless the destination requires a stronger approach:

- Start the session in middleware.
- Store the logged-in employee or user in `$_SESSION`.
- Guard protected routes in controllers.
- Redirect anonymous users to the login page.
- Pass the current user to Twig for navbar and page context.

If the destination project needs stricter security, keep the same flow but replace PIN auth, session storage details, and credential handling with safer equivalents.

### 9. Adapt data and integrations carefully

Treat the source tables and API payloads as implementation details, not universal schema. When moving to another project:

- Rename tables and fields inside models only.
- Keep controllers stable where possible.
- Keep service methods as the translation layer between external payloads and internal models.
- Preserve the source app's pattern of flattening and enriching database rows before rendering.
- Rework status maps, booking payloads, and sync logic only inside model or service code.

### 10. Verify the clone

Run this checklist before considering the replication complete:

- Composer autoloading resolves all `App\\` classes.
- `public/index.php` boots the app without container or middleware errors.
- `base_path` works in links, redirects, and form actions.
- Login and logout flow work.
- At least one HTML page renders through Twig.
- At least one JSON endpoint returns the expected payload and status code.
- PDO models can query and mutate destination tables.
- Flash messages render in the base layout.
- Tailwind, jQuery, and DataTables load on the intended pages.
- One full vertical slice works end to end.

## Implementation Notes

Use `references/source-app-architecture.md` when you need the exact source files to inspect or copy from. Read only the sections that match the slice you are porting.

If the user asks for a literal clone into a new workspace, scaffold the destination first, then port in this order:

1. `composer.json`
2. `public/index.php`
3. `config/container.php`
4. `config/routes.php`
5. `src/Middleware/SessionMiddleware.php`
6. `src/Services/`
7. `src/Models/`
8. `src/Controllers/`
9. `templates/layouts/base.html.twig`
10. page templates and page-specific scripts

If the user asks only for instructions, provide a project-specific migration checklist derived from these rules instead of editing code.

