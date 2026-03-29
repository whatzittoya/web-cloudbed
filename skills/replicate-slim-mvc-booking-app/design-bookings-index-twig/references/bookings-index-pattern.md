# Bookings Index Pattern

Use this reference when redesigning `templates/bookings/index.html.twig`.

## Purpose

The page is a bookings list with filters and row-level actions. The current template is functionally useful but visually older than the rest of the app. The redesign should preserve the list workflow while aligning it with the newer Tailwind-based screens.

## Source Files To Read

Read these files in this order:

1. `templates/bookings/index.html.twig`
2. `templates/layouts/base.html.twig`
3. `templates/dashboard.html.twig`
4. `templates/reservations/index.html.twig`
5. `src/Models/Booking.php`
6. `src/Controllers/BookingApiController.php`
7. `config/routes.php`

## What To Reuse From Each File

### `templates/bookings/index.html.twig`

Reuse:

- booking date heading
- stats concepts
- filters
- bookings list columns
- check-in and undo intent
- empty-state logic

Replace:

- Bootstrap classes
- table-dark header styling
- direct form-post buttons when AJAX is desired
- hardcoded `/bookings/...` links without `base_path`

### `templates/layouts/base.html.twig`

Reuse:

- page shell
- navbar structure
- flash banner styling
- main content spacing
- overall Tailwind stack

### `templates/dashboard.html.twig`

Reuse:

- fade and slide reveal behavior
- loading state treatment
- button disabling during async work
- `fetch(...)` request pattern
- inline success and error feedback
- class toggling using `hidden`, opacity, and transform

Key motion behaviors worth copying:

- collapse a header or helper panel before showing results
- render results hidden first, then animate them into view
- use loading indicators instead of blocking the full page

### `templates/reservations/index.html.twig`

Reuse:

- stats card presentation
- filter card layout
- list container styling
- alert area
- modal and confirmation structure
- delayed reload pattern after success where needed

## Recommended Redesign Shape

Use this page structure:

```text
Header
Alert area
Stats grid
Filter card
Results container
Optional modal
Script block
```

Choose one of these result layouts:

- Dense admin table: best if the user scans many bookings quickly
- Card list: best if each booking needs richer context and mobile-first presentation

For this app, default to a refined table or hybrid table/card layout because the source page is operational and admin-oriented.

## Motion Blueprint

Use small, controlled transitions:

- Result container enters with `opacity` plus `translateY`
- Row or card success state flashes green briefly, then updates
- Loading uses inline spinner or text swap
- Filters can collapse on small screens if needed
- Modals fade in on a darkened backdrop

Suggested timing:

- `200ms` for button state changes
- `300ms` for row removal or status updates
- `500ms` for panel reveal or collapse

## AJAX Blueprint

Prefer these endpoint types:

- `GET` for filtered list refresh or detail fetch
- `POST` for check-in
- `POST` for undo

Suggested client flow for a row action:

1. Capture booking id
2. Disable the action button
3. Replace button contents with a spinner
4. Submit `fetch(...)`
5. Parse JSON
6. Update badge, button, stats, and alerts
7. Re-enable or replace controls

Suggested JSON response shape:

```json
{
  "success": true,
  "message": "Guest checked in successfully.",
  "data": {
    "id": 12,
    "status": "checked_in"
  }
}
```

## Backend Caveat

The repository currently has booking model and API pieces, but bookings routes are not active in `config/routes.php`. If the redesign requires working AJAX, add or repair the booking routes and ensure the controller returns JSON for async calls.

## Common Mistakes To Avoid

- Mixing Bootstrap classes into the Tailwind layout
- Keeping hardcoded URLs instead of `{{ base_path }}`
- Using full-page reloads for every row action
- Animating entire tables excessively
- Updating a status badge without also updating the action button
- Building AJAX on top of routes that do not exist

