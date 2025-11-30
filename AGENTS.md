# Agent Guidelines for my_crm

- Never run `git commit` unless I give the prompt `Commit all changes.`
- Since we are using Laravel Sail for development all commands that start with `php artisan` should now be run as `./vendor/bin/sail artisan`.

## Tech Stack
Laravel 12 + Filament 4 admin panel, Pest for testing, Vite for asset building, PHP 8.4+, Laravel Sail for development

## Commands
- Running migrations: `./vendor/bin/sail artisan migrate`
- Test all: `./vendor/bin/sail test` or `./vendor/bin/sail composer test`
- Test single file: `./vendor/bin/sail test tests/Feature/ExampleTest.php`
- Test single test: `./vendor/bin/sail test --filter=test_name`
- Lint/format: `./vendor/bin/sail pint` (Laravel Pint follows PSR-12)
- Dev server: `composer dev` (runs server, queue, logs, and vite concurrently)
- Build assets: `./vendor/bin/sail npm run build`

## Code Style
- Indentation: 4 spaces for PHP/YAML, 2 spaces for package.json
- Line endings: LF, UTF-8, trim trailing whitespace, final newline required
- PHP: PSR-12 standard via Laravel Pint, strict types optional
- Imports: Group by vendor (Illuminate first), then alphabetical
- Types: Use PHPDoc types (`@var`, `@return`) and native types where possible
- Naming: PascalCase for classes, camelCase for methods/properties, snake_case for DB columns
- Tests: Use Pest syntax with `test()` and `expect()`, organize in Feature/Unit directories
- Error handling: Use Laravel exceptions, validate requests, return appropriate HTTP status codes
