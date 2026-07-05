# Testing

## Pest

This project uses Pest for testing.

- Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should **not** include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest`, not `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do **not** delete tests without approval.

## Test Setup

- When creating models for tests, use the models' factories. Check whether the factory has custom states that can be used before manually setting up the model.
- Faker: use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions on whether to use `$this->faker` or `fake()`.
- Use `php artisan make:test [options] {name}` to create a feature test; pass `--unit` for a unit test. Most tests should be feature tests.
