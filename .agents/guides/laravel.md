# Laravel Conventions

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (migrations, controllers, models, etc.). List commands with `php artisan list` and check parameters with `php artisan [command] --help`.
- If creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands, plus the correct `--options` for the desired behavior.

## Model Creation

- When creating new models, also create useful factories and seeders for them. Ask the user if they need anything else, using `php artisan make:model --help` to check available options.

## APIs & Eloquent Resources

- For APIs, default to Eloquent API Resources and API versioning — unless existing API routes don't, in which case follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.
