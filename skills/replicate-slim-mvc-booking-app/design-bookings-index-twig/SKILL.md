---
name: design-bookings-index-twig
description: Design or redesign `templates/bookings/index.html.twig` and similar Twig list pages to match this app's modern UI system. Use when Codex needs to replicate the layout, Tailwind styling, motion transitions, and AJAX interaction patterns seen in the newer booking, reservation, check-in, or dashboard screens while adapting the older bookings index page.
---

# Design Bookings Index Twig

## Overview

Use this skill to turn the legacy `templates/bookings/index.html.twig` page into a page that feels like the newer Quinos screens. Keep the page's booking-list purpose, but borrow the modern shell, card treatment, transitions, and fetch-based interaction model from the newer templates.

Read `references/bookings-index-pattern.md` before editing the page.

## Workflow

### 1. Audit the current page and source patterns

Treat `templates/bookings/index.html.twig` as the data and feature baseline, not the design baseline.

Compare it against these newer references:

- `templates/layouts/base.html.twig` for the shared shell and Tailwind stack
- `templates/dashboard.html.twig` for animated reveal, state transitions, and fetch flows
- `templates/reservations/index.html.twig` for stats cards, filters, alerts, modal patterns, and action handling

### 2. Preserve the purpose of the page

Keep these functional responsibilities unless the user asks to change them:

- Show the selected date
- Show booking stats
- Provide search and status filters
- Render the bookings list
- Expose check-in and undo actions
- Handle empty states clearly

Change the visual system and interaction model, not the page's core job.

### 3. Replace the legacy visual language

The current page uses Bootstrap-like classes that do not match the active base layout. Replace them with the same Tailwind conventions used elsewhere in the app.

Use these layout rules:

- Start with a compact page header: title, subtitle, and optional date context
- Render stats as rounded cards in a responsive grid
- Put filters inside a white card with soft borders
- Render the list inside a white rounded container with either:
  - a modern table for dense admin scanning, or
  - stacked cards for more visual emphasis on mobile
- Use gray surfaces, blue as the primary action color, green for success, red for destructive or cancelled states
- Prefer `rounded-xl` and `rounded-2xl`, `border-gray-200`, and restrained shadows

Do not introduce Bootstrap classes, Bootstrap Icons, or mixed design systems unless the destination explicitly requires them.

### 4. Rebuild the page structure in this order

1. Page heading block
2. Flash or inline alert area
3. Stats card grid
4. Filter card
5. Results container
6. Empty state
7. Detail or confirmation modal if the interaction needs it
8. Page-specific script block

Keep page-specific JavaScript in `{% block scripts %}` unless the project already extracts it.

### 5. Apply the motion pattern

Use the motion system from `templates/dashboard.html.twig`, not generic animation libraries.

Apply these transition rules:

- Use opacity transitions for fade in and fade out
- Use `translateY(...)` for subtle slide-up reveals
- Use `max-height`, spacing, or padding changes for collapsible headers or panels
- Use `hidden` plus class toggles for state changes
- Keep transitions between `200ms` and `500ms`
- Use loading spinners or button text changes during async actions

Good uses of motion on the bookings index page:

- Reveal filtered results after an AJAX refresh
- Animate an inline success or error alert
- Collapse the filter area on mobile if needed
- Fade out a row or card after successful check-in
- Animate modal open and close

Do not animate everything. Reserve motion for search, action confirmation, list refresh, and state changes.

### 6. Convert actions to AJAX-first behavior

Prefer fetch-based interactions over full-page form submissions for check-in, undo, filter refresh, or detail loading.

Use this flow:

1. User clicks an action
2. Disable the button immediately
3. Swap the label to a loading state
4. Send `fetch(...)`
5. Parse JSON
6. Update the row, card, stats, or alert in place
7. Restore or replace the button state

Keep the response contract explicit:

- `success`
- `message`
- optional `data`
- appropriate HTTP status codes

If the existing page still posts to `/bookings/{id}/checkin` or `/bookings/{id}/cancel-checkin`, either:

- keep those endpoints and make them return JSON, or
- add dedicated AJAX endpoints for the bookings index

Do not keep hardcoded action paths like `/bookings/...` without checking whether routes actually exist in the current app. Prefer `{{ base_path }}` in links and fetch URLs.

### 7. Update the backend contract when needed

Before wiring AJAX, confirm the backing route and controller actually exist.

The current repository contains:

- `src/Models/Booking.php`
- `src/Controllers/BookingApiController.php`
- no active bookings routes in `config/routes.php`

Treat the bookings page as a partial or older slice. When implementing AJAX for it, add or repair the route/controller path instead of assuming the existing template is wired end to end.

### 8. Keep state updates local and obvious

After a successful async action, update the UI directly instead of forcing a full reload when possible.

Examples:

- Change the booking status badge from confirmed to checked in
- Replace the action button with an undo button
- Remove the row with a fade-out if the business flow should hide processed bookings
- Update stats totals if the page shows them
- Show a green inline alert near the top of the page

If the full page must refresh, delay it briefly after showing success feedback.

## Design Rules

- Keep the active app shell from `templates/layouts/base.html.twig`
- Use Tailwind utility classes consistently
- Keep typography compact and admin-oriented
- Use white cards on a gray page background
- Keep actions large enough to scan and tap quickly
- Use badges or pills for booking state
- Preserve empty and error states as first-class UI

## AJAX Rules

- Use `fetch`, not jQuery AJAX, unless the surrounding page already depends on jQuery plugins
- Return JSON from action endpoints
- Handle `.catch(...)` with a visible error state
- Disable buttons during requests to avoid duplicate submissions
- Use optimistic updates only when rollback is simple

## Implementation Notes

When asked to redesign the page, do this:

1. Read `templates/bookings/index.html.twig`
2. Read `references/bookings-index-pattern.md`
3. Rebuild the Twig structure with Tailwind classes
4. Add a dedicated script block for transitions and fetch flows
5. Confirm or add the required routes and controller responses
6. Verify empty, loading, success, and error states

When asked only for design guidance, produce a page-specific implementation plan instead of editing code.

