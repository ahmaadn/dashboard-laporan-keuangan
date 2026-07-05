# PHP Style & Pint Formatting

## PHP Conventions

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

## Laravel Pint

- If you have modified any PHP file, you **must** run `vendor/bin/pint --dirty --format agent` before finalizing changes to match the project's expected style.
- Do **not** run `vendor/bin/pint --test --format agent`; run `vendor/bin/pint --format agent` to fix formatting issues directly.
