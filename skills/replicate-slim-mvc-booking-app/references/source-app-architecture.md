# Source App Architecture

Use this reference to inspect the exact source files before cloning the app into another project.

## Stack Summary

- Framework: Slim 4
- Dependency injection: PHP-DI via `php-di/slim-bridge`
- Views: Twig
- Frontend styling: Tailwind CDN
- Frontend behavior: jQuery and DataTables CDN
- Persistence: PDO with MySQL settings
- External integration: Guzzle HTTP client wrapped in a service
- Session/auth: custom middleware plus controller checks

## Bootstrap And Configuration

- `public/index.php`
  Builds the container, creates the Slim app, sets `base_path`, adds middleware, loads routes, and runs the app.
- `config/container.php`
  Defines `settings`, `PDO::class`, `Client::class`, `FinnsApiService::class`, `Sale::class`, `Messages::class`, and `Twig::class`.
- `config/routes.php`
  Registers auth, reservation, and check-in/check-out routes.
- `src/Middleware/SessionMiddleware.php`
  Starts the PHP session before controllers run.

## MVC Layout

### Controllers

- `src/Controllers/AuthController.php`
  Handles login form, login POST, logout, and dashboard rendering.
- `src/Controllers/ReservationController.php`
  Handles reservation list rendering, API sync trigger, and deletion.
- `src/Controllers/CheckInController.php`
  Handles booking-code search plus check-in and check-out JSON actions.
- `src/Controllers/BookingApiController.php`
  Shows a REST-style controller pattern for JSON CRUD.

### Models

- `src/Models/Employee.php`
  Reads employee records for login and identity lookup.
- `src/Models/Reservation.php`
  Owns the largest data flow: list queries, detail joins, booking-code lookup, sync inserts, stats, and deletes.
- `src/Models/Sale.php`
  Looks up sales headers and line items for checkout payloads.

The repository currently does not contain `src/Models/Booking.php`. Treat `src/Controllers/BookingApiController.php` as an incomplete or currently unused REST-style example unless that model is added in the destination project.

### Services

- `src/Services/FinnsApiService.php`
  Wraps upstream API calls for reservation pull, guest check-in, and guest check-out.

## View System

- `templates/layouts/base.html.twig`
  Defines the shared HTML shell, navigation slot, flash banners, main content area, and footer.
- `templates/auth/login.html.twig`
  Uses the base layout but removes the navbar for the login screen.
- `templates/dashboard.html.twig`
  Shows the main check-in and check-out experience with inline JavaScript.
- `templates/reservations/index.html.twig`
  Shows reservation stats, sync controls, DataTables listing, detail modal, and sync confirmation modal.
- `templates/bookings/*.html.twig`
  Contains additional booking-oriented templates where present.

## UI Conventions To Preserve

- Use white cards on gray backgrounds with blue primary actions.
- Use `rounded-xl` and `rounded-2xl` heavily for forms, cards, buttons, and modals.
- Use slim borders such as `border-gray-200` instead of heavy shadows.
- Use small uppercase section labels and muted helper text.
- Use status chips for booking state.
- Keep page-specific JavaScript close to the Twig template when behavior is not reused elsewhere.

## Source Files To Inspect For A Full Clone

Read these files in order when reproducing the project:

1. `composer.json`
2. `public/index.php`
3. `config/container.php`
4. `config/routes.php`
5. `src/Middleware/SessionMiddleware.php`
6. `src/Controllers/AuthController.php`
7. `src/Controllers/ReservationController.php`
8. `src/Controllers/CheckInController.php`
9. `src/Models/Employee.php`
10. `src/Models/Reservation.php`
11. `src/Models/Sale.php`
12. `src/Services/FinnsApiService.php`
13. `templates/layouts/base.html.twig`
14. `templates/auth/login.html.twig`
15. `templates/dashboard.html.twig`
16. `templates/reservations/index.html.twig`

## Porting Guidance By Concern

### To copy the framework shell

Inspect:

- `composer.json`
- `public/index.php`
- `config/container.php`
- `config/routes.php`
- `src/Middleware/SessionMiddleware.php`

### To copy the MVC boundaries

Inspect:

- `src/Controllers/*.php`
- `src/Models/*.php`
- `src/Services/*.php`

### To copy the design system

Inspect:

- `templates/layouts/base.html.twig`
- `templates/auth/login.html.twig`
- `templates/dashboard.html.twig`
- `templates/reservations/index.html.twig`

### To copy one working vertical slice

Inspect this sequence:

1. reservation route in `config/routes.php`
2. `src/Controllers/ReservationController.php`
3. `src/Models/Reservation.php`
4. `templates/reservations/index.html.twig`
