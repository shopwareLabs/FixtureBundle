# Shopware FixtureBundle

The FixtureBundle provides a flexible and organized way to load test data into your Shopware 6 application. It supports dependency management, priority-based execution, and group filtering.

## Installation

The bundle is automatically registered when placed in the `custom/static-plugins/FixtureBundle` directory.

## Creating Fixtures

### Basic Fixture

Create a class that implements `FixtureInterface` and add the `#[Fixture]` attribute:

```php
<?php

declare(strict_types=1);

namespace App\Fixture;

use Shopware\FixtureBundle\FixtureInterface;
use Shopware\FixtureBundle\Attribute\Fixture;

#[Fixture]
class BasicFixture implements FixtureInterface
{
    public function load(): void
    {
        // Your fixture logic here
        echo "Loading basic fixture...\n";
    }
}
```

### Fixture with Priority

Higher priority fixtures are executed first:

```php
#[Fixture(priority: 100)]
class HighPriorityFixture implements FixtureInterface
{
    public function load(): void
    {
        // This runs before fixtures with lower priority
    }
}
```

### Fixture with Dependencies

Specify other fixtures that must be loaded before this one:

```php
#[Fixture(
    priority: 50,
    dependsOn: [CategoryFixture::class]
)]
class ProductFixture implements FixtureInterface
{
    public function load(): void
    {
        // CategoryFixture will always run before this
    }
}
```

### Fixture with Groups

Organize fixtures into groups for selective loading:

```php
#[Fixture(
    groups: ['test-data', 'products']
)]
class ProductTestDataFixture implements FixtureInterface
{
    public function load(): void
    {
        // This fixture belongs to both 'test-data' and 'products' groups
    }
}
```

### Complete Example

```php
<?php

declare(strict_types=1);

namespace App\Fixture;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\FixtureBundle\FixtureInterface;
use Shopware\FixtureBundle\Attribute\Fixture;

#[Fixture(
    priority: 100,
    groups: ['catalog', 'test-data']
)]
class CategoryFixture implements FixtureInterface
{
    public function __construct(
        private readonly EntityRepository $categoryRepository,
        private readonly Connection $connection
    ) {
    }

    public function load(): void
    {
        $categories = [
            [
                'id' => Uuid::randomHex(),
                'name' => 'Electronics',
                'active' => true,
            ],
            [
                'id' => Uuid::randomHex(),
                'name' => 'Clothing',
                'active' => true,
            ],
        ];

        foreach ($categories as $category) {
            $this->categoryRepository->create([$category], Context::createDefaultContext());
        }
    }
}
```

## Commands

### Loading Fixtures

Load all fixtures:
```bash
bin/console fixture:load
```

Load only fixtures from a specific group:
```bash
bin/console fixture:load --group=test-data
```

### Listing Fixtures

View all available fixtures and their execution order:
```bash
bin/console fixture:list
```

List fixtures from a specific group:
```bash
bin/console fixture:list --group=test-data
```

Example output:
```
Available Fixtures
==================

 ------- -------------------- ---------- ----------------- ------------------
  Order   Class                Priority   Groups            Depends On
 ------- -------------------- ---------- ----------------- ------------------
  1       CategoryFixture      100        catalog, test-    -
                                          data
  2       ManufacturerFixture  90         catalog           -
  3       ProductFixture       50         catalog, test-    CategoryFixture,
                                          data              ManufacturerFixture
  4       CustomerFixture      0          customers         -
 ------- -------------------- ---------- ----------------- ------------------

 [OK] Found 4 fixture(s).
```

## Fixture Attributes

The `#[Fixture]` attribute accepts the following parameters:

- **priority** (int, default: 0): Higher values execute first
- **dependsOn** (array, default: []): Array of fixture class names that must run before this fixture
- **groups** (array, default: ['default']): Array of group names this fixture belongs to

## Execution Order

Fixtures are executed in an order determined by:

1. **Dependencies**: Fixtures with dependencies always run after their dependencies
2. **Priority**: Among fixtures without dependency relationships, higher priority runs first
3. **Circular dependency detection**: The system will throw an exception if circular dependencies are detected

## Service Registration

Fixtures are automatically discovered and registered if they:
1. Implement the `FixtureInterface`
2. Have the `#[Fixture]` attribute
3. Are registered as services (auto-configuration is enabled by default)

## Theme Fixtures

The FixtureBundle provides a convenient way to configure theme settings through fixtures using the `ThemeFixtureLoader` and `ThemeFixtureDefinition` classes.

### Basic Theme Fixture

```php
<?php

declare(strict_types=1);

namespace App\Fixture;

use Shopware\FixtureBundle\Attribute\Fixture;
use Shopware\FixtureBundle\FixtureInterface;
use Shopware\FixtureBundle\Helper\Theme\ThemeFixtureDefinition;
use Shopware\FixtureBundle\Helper\Theme\ThemeFixtureLoader;

#[Fixture(groups: ['theme-config'])]
class ThemeFixture implements FixtureInterface
{
    public function __construct(
        private readonly ThemeFixtureLoader $themeFixtureLoader
    ) {
    }

    public function load(): void
    {
        $this->themeFixtureLoader->load(
            (new ThemeFixtureDefinition('Shopware default theme'))
                ->config('sw-color-brand-primary', '#ff6900')
                ->config('sw-border-radius-default', '8px')
                ->config('sw-font-family-base', '"Inter", sans-serif')
                ->config('sw-background-color', '#f8f9fa')
        );
    }
}
```

### Multiple Theme Configuration

```php
#[Fixture(groups: ['theme-config', 'branding'])]
class BrandingThemeFixture implements FixtureInterface
{
    public function __construct(
        private readonly ThemeFixtureLoader $themeFixtureLoader
    ) {
    }

    public function load(): void
    {
        // Configure main storefront theme
        $this->themeFixtureLoader->load(
            (new ThemeFixtureDefinition('Shopware default theme'))
                ->config('sw-color-brand-primary', '#007bff')
                ->config('sw-color-brand-secondary', '#6c757d')
        );

        // Configure custom theme if available
        try {
            $this->themeFixtureLoader->load(
                (new ThemeFixtureDefinition('Custom Theme'))
                    ->config('custom-header-color', '#ffffff')
                    ->config('custom-footer-background', '#333333')
            );
        } catch (FixtureException $e) {
            // Custom theme not available, skip
        }
    }
}
```

### Setting Logo

```php
#[Fixture(groups: ['theme-config', 'branding'])]
class BrandingThemeFixture implements FixtureInterface
{
    public function __construct(
        private readonly ThemeFixtureLoader $themeFixtureLoader
    ) {
    }

    public function load(): void
    {
        // Will be uploaded just once and reused based on file content
        $logo = $this->mediaHelper->upload(__DIR__ . '/shop.png', $this->mediaHelper->getDefaultFolder(ThemeDefinition::ENTITY_NAME)->getId());
    
        // Configure main storefront theme
        $this->themeFixtureLoader->load(
            (new ThemeFixtureDefinition('Shopware default theme'))
                ->config('sw-color-brand-primary', '#007bff')
                ->config('sw-color-brand-secondary', '#6c757d')
                ->config('sw-logo-desktop', $logo)
                ->config('sw-logo-tablet', $logo)
                ->config('sw-logo-mobile', $logo)
        );
    }
}
```

### Theme Fixture Features

- **Fluent Configuration**: Chain multiple `->config()` calls for readability
- **Automatic Theme Discovery**: Finds themes by name automatically
- **Change Detection**: Only updates and recompiles when configuration actually changes
- **Error Handling**: Throws `FixtureException::themeNotFound()` if theme doesn't exist
- **Automatic Recompilation**: Theme is automatically recompiled after configuration changes

### Available Configuration Fields

Common theme configuration fields include:
- `sw-color-brand-primary` - Primary brand color
- `sw-color-brand-secondary` - Secondary brand color  
- `sw-border-radius-default` - Default border radius
- `sw-font-family-base` - Base font family
- `sw-background-color` - Background color
- `sw-logo-desktop` - Desktop logo
- `sw-logo-mobile` - Mobile logo
- `sw-logo-tablet` - Tablet logo
- `sw-logo-desktop-height` - Desktop logo height
- `sw-logo-mobile-height` - Mobile logo height

*Note: Available fields depend on your theme's configuration schema defined in `theme.json`*

## Custom Field Fixtures

The FixtureBundle provides helper classes to easily create and manage custom fields through fixtures using `CustomFieldSetFixtureLoader` and related definition classes.

### Basic Custom Field Fixture

```php
<?php

declare(strict_types=1);

namespace Acme\Fixture;

use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\FixtureBundle\Attribute\Fixture;
use Shopware\FixtureBundle\FixtureInterface;
use Shopware\FixtureBundle\Helper\CustomField\CustomFieldFixtureDefinition;
use Shopware\FixtureBundle\Helper\CustomField\CustomFieldSetFixtureDefinition;
use Shopware\FixtureBundle\Helper\CustomField\CustomFieldSetFixtureLoader;

#[Fixture]
class CustomFieldFixture implements FixtureInterface
{
    public function __construct(
        private readonly CustomFieldSetFixtureLoader $customFieldSetFixtureLoader
    ) {
    }

    public function load(): void
    {
        $this->customFieldSetFixtureLoader->load(
            (new CustomFieldSetFixtureDefinition('Product Specifications', 'product_specs'))
                ->relation('product')
                ->field(
                    (new CustomFieldFixtureDefinition('weight', CustomFieldTypes::FLOAT))
                        ->label('en-GB', 'Weight (kg)')
                        ->label('de-DE', 'Gewicht (kg)')
                        ->placeholder('en-GB', 'Enter product weight')
                        ->helpText('en-GB', 'Product weight in kilograms')
                        ->position(10)
                )
                ->field(
                    (new CustomFieldFixtureDefinition('dimensions', CustomFieldTypes::TEXT))
                        ->label('en-GB', 'Dimensions')
                        ->placeholder('en-GB', 'L x W x H')
                        ->position(20)
                )
                ->field(
                    (new CustomFieldFixtureDefinition('warranty_period', CustomFieldTypes::INT))
                        ->label('en-GB', 'Warranty Period (months)')
                        ->config(['min' => 0, 'max' => 120])
                        ->position(30)
                )
        );
    }
}
```

## Best Practices

1. **Use meaningful names**: Name your fixtures clearly to indicate what data they create
2. **Organize with groups**: Use groups to categorize fixtures (e.g., 'test-data', 'demo-data', 'performance-test', 'theme-config')
3. **Declare dependencies explicitly**: Always declare dependencies to ensure correct execution order
4. **Keep fixtures focused**: Each fixture should have a single responsibility
5. **Make fixtures idempotent**: Fixtures should be able to run multiple times without errors
6. **Use dependency injection**: Inject the services you need rather than accessing the container directly
7. **Handle theme errors gracefully**: Use try-catch blocks when configuring optional themes
