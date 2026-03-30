# Contributing to Laravel CA Log

Thank you for considering contributing to Laravel CA Log! This document provides guidelines and steps for contributing.

## Prerequisites

- **PHP** 8.4 or higher
- **Composer** 2.x
- **Git**
- **Laravel** 12.x or 13.x (for integration testing)

## Setup

1. Fork and clone the repository:

```bash
git clone git@github.com:your-username/laravel-ca-log.git
cd laravel-ca-log
```

2. Install dependencies:

```bash
composer install
```

3. Verify your setup:

```bash
./vendor/bin/pest
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

## Branching Strategy

- `main` — Stable, release-ready code. Never push directly.
- `develop` — Integration branch for work in progress.
- `feat/description` — New features.
- `fix/description` — Bug fixes.
- `docs/description` — Documentation-only changes.
- `refactor/description` — Code refactoring without behavior changes.

## Coding Standards

This project follows the Laravel coding style enforced by [Laravel Pint](https://laravel.com/docs/pint):

```bash
# Check formatting
./vendor/bin/pint --test

# Auto-fix formatting
./vendor/bin/pint
```

Static analysis is performed with [PHPStan](https://phpstan.org/) at level 9 via [Larastan](https://github.com/larastan/larastan):

```bash
./vendor/bin/phpstan analyse
```

## Tests

Tests are written with [Pest 3](https://pestphp.com/):

```bash
# Run all tests
./vendor/bin/pest

# Run with coverage (minimum 80% required)
./vendor/bin/pest --coverage --min=80

# Run a specific test file
./vendor/bin/pest tests/Unit/CefFormatterTest.php
```

## Commit Messages

This project follows [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` — New feature
- `fix:` — Bug fix
- `docs:` — Documentation only
- `chore:` — Maintenance tasks
- `refactor:` — Code refactoring
- `test:` — Adding or updating tests

Examples:

```
feat: add syslog formatter with RFC 5424 structured data
fix: correct CEF severity mapping for warning level
docs: update README with context enrichment examples
test: add unit tests for LogFilter matching
```

## Pull Request Process

1. Fork the repository and create your branch from `develop`.
2. Write or update tests for your changes.
3. Ensure all checks pass: Pest, Pint, PHPStan.
4. Update documentation (README, CHANGELOG, ARCHITECTURE) as needed.
5. Submit a pull request to `develop` using the PR template.
6. Wait for code review — at least one approval is required.

## PHP 8.4 Specifics

When contributing, use PHP 8.4 features where appropriate:

- **Readonly classes and properties** for DTOs and value objects.
- **Constructor property promotion** for dependency injection.
- **Named arguments** for improved readability.
- **`match` expressions** instead of `switch` statements.
- **Union types and intersection types** for strict typing.
- **Enums** instead of class constants where applicable.
- **`#[\Override]` attribute** when overriding parent methods.

## Code of Conduct

Please review and follow our [Code of Conduct](CODE_OF_CONDUCT.md).
