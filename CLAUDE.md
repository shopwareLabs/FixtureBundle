# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Shopware 6 bundle called "FixtureBundle" that provides organized test data loading for Shopware applications. It supports dependency management, priority-based execution, and group filtering for fixtures.

## Development Commands

### Testing
```bash
vendor/bin/phpunit                                                           # Run all tests
vendor/bin/phpunit tests/Integration/                                       # Run all integration tests
vendor/bin/phpunit tests/Integration/Helper/CustomField/CustomFieldSetFixtureLoaderTest.php # Run CustomField tests
vendor/bin/phpunit tests/Integration/Helper/Theme/ThemeFixtureLoaderTest.php # Run Theme tests
vendor/bin/phpunit tests/Integration/Helper/Customer/CustomerFixtureLoaderTest.php # Run Customer tests
```

### Docker Development Environment
```bash
docker-compose up -d                  # Start development environment (MariaDB + Shopware)
# Web service available at http://localhost:8000
```

### Shopware Fixture Commands
```bash
bin/console fixture:load                        # Load all fixtures
bin/console fixture:load --group=test-data      # Load specific fixture group
bin/console fixture:list                        # List all available fixtures
bin/console fixture:list --group=test-data      # List fixtures in specific group
```

## Architecture

### Core Components
- **FixtureInterface**: Base interface for all fixtures with dependency management
- **FixtureAttributes**: PHP attributes for configuring fixture metadata (`#[Fixture]`, `#[Group]`, `#[Depends]`)
- **LoadFixturesCommand**: Console command for loading fixtures with filtering support
- **ListFixturesCommand**: Console command for discovering and listing fixtures

### Helper Classes
- **ThemeFixture**: Base class for theme configuration fixtures
- **CustomFieldFixture**: Helper for creating custom field sets and fields
- **MediaFixture**: Utility for uploading and managing media files

### Service Configuration
Services are defined in `src/Resources/config/services.xml` with proper DI container integration.

## Key Development Patterns

### Creating Fixtures
Fixtures implement `FixtureInterface` and use PHP attributes for configuration:
```php
#[Fixture(priority: 100)]
#[Group(['basic', 'products'])]
#[Depends([CategoryFixture::class])]
class ProductFixture implements FixtureInterface
{
    public function load(Context $context): void { ... }
}
```

### Bundle Structure
- Main bundle class: `src/FixtureBundle.php`
- Commands: `src/Command/`
- Interfaces: `src/FixtureInterface.php`
- Helpers: `src/` (ThemeFixture, CustomFieldFixture, MediaFixture)
- DI config: `src/Resources/config/services.xml`

### Dependencies
- Requires Shopware Core ^6.6 and Storefront ^6.6
- Uses PHPUnit ^12.2 for testing
- Composer type: `shopware-bundle`