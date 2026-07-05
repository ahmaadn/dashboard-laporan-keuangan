# Frontend

## UI Framework: Bootstrap v5

This project uses **Bootstrap v5** for styling — **not** Tailwind CSS.

- Follow Bootstrap v5 component and utility conventions (grid, cards, modals, forms, etc.).
- Do **not** introduce Tailwind classes or the `@tailwindcss/vite` plugin.
- Bootstrap is imported via `resources/css/app.css` (`@import 'bootstrap/dist/css/bootstrap.min.css'`) and `resources/js/app.js` (`import 'bootstrap/dist/js/bootstrap.bundle.min.js'` — includes Popper).

> Note: Laravel's own vendor views (pagination defaults, exception renderer, health page) still reference Tailwind internally — these are framework internals and must not be edited. Application views use Bootstrap exclusively.

## Frontend Bundling (Vite)

- If a frontend change isn't reflected in the UI, the user may need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.
- On `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`, run `npm run build` or ask the user to run `npm run dev` / `composer run dev`.
