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

## Best Practices

1. **Use meaningful names**: Name your fixtures clearly to indicate what data they create
2. **Organize with groups**: Use groups to categorize fixtures (e.g., 'test-data', 'demo-data', 'performance-test')
3. **Declare dependencies explicitly**: Always declare dependencies to ensure correct execution order
4. **Keep fixtures focused**: Each fixture should have a single responsibility
5. **Make fixtures idempotent**: Fixtures should be able to run multiple times without errors
6. **Use dependency injection**: Inject the services you need rather than accessing the container directly

## Troubleshooting

### Circular Dependency Error
If you see "Circular dependency detected for fixture", check your `dependsOn` declarations to ensure there are no circular references.

### Fixture Not Found
Ensure your fixture:
- Is in a directory that's auto-loaded
- Implements `FixtureInterface`
- Has the `#[Fixture]` attribute
- Is registered as a service

### Dependencies Not in Group
When loading fixtures by group, dependencies outside the group are skipped. Ensure all required dependencies are in the same group or use no group filter.