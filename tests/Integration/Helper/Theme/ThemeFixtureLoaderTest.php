<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Tests\Integration\Helper\Theme;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\FixtureBundle\FixtureException;
use Shopware\FixtureBundle\Helper\Theme\ThemeFixtureDefinition;
use Shopware\FixtureBundle\Helper\Theme\ThemeFixtureLoader;
use Shopware\Storefront\Theme\ThemeEntity;
use Shopware\Storefront\Theme\ThemeService;

class ThemeFixtureLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use DatabaseTransactionBehaviour;

    private ThemeFixtureLoader $loader;
    private EntityRepository $themeRepository;
    private ThemeService $themeService;
    private Context $context;

    protected function setUp(): void
    {
        $this->themeRepository = $this->getContainer()->get('theme.repository');
        $this->themeService = $this->getContainer()->get(ThemeService::class);
        $this->loader = new ThemeFixtureLoader(
            $this->themeRepository,
            $this->themeService
        );
        $this->context = Context::createDefaultContext();
    }

    public function testApplyUpdatesThemeConfiguration(): void
    {
        // Create a test theme first
        $testTheme = $this->createTestTheme('Test Theme');

        $definition = (new ThemeFixtureDefinition('Test Theme'))
            ->config('sw-color-brand-primary', '#ff0000')
            ->config('sw-color-brand-secondary', '#00ff00')
            ->config('sw-font-family-base', 'Arial, sans-serif');

        $this->loader->apply($definition);

        // Reload the theme to verify changes
        $updatedTheme = $this->findThemeByName('Test Theme');
        static::assertNotNull($updatedTheme);

        $configValues = $updatedTheme->getConfigValues();
        static::assertNotNull($configValues);

        // Verify the configuration was applied
        static::assertArrayHasKey('sw-color-brand-primary', $configValues);
        static::assertSame('#ff0000', $configValues['sw-color-brand-primary']['value']);

        static::assertArrayHasKey('sw-color-brand-secondary', $configValues);
        static::assertSame('#00ff00', $configValues['sw-color-brand-secondary']['value']);

        static::assertArrayHasKey('sw-font-family-base', $configValues);
        static::assertSame('Arial, sans-serif', $configValues['sw-font-family-base']['value']);
    }

    public function testApplyMergesWithExistingConfiguration(): void
    {
        // Create a test theme with initial configuration
        $testTheme = $this->createTestTheme('Merge Test Theme', [
            'sw-color-brand-primary' => ['value' => '#blue'],
            'existing-config' => ['value' => 'keep-this'],
        ]);

        $definition = (new ThemeFixtureDefinition('Merge Test Theme'))
            ->config('sw-color-brand-primary', '#red')
            ->config('new-config', 'add-this');

        $this->loader->apply($definition);

        // Verify existing config is preserved and new config is added
        $updatedTheme = $this->findThemeByName('Merge Test Theme');
        static::assertNotNull($updatedTheme);

        $configValues = $updatedTheme->getConfigValues();
        static::assertNotNull($configValues);

        // Verify new config was applied
        static::assertSame('#red', $configValues['sw-color-brand-primary']['value']);
        static::assertSame('add-this', $configValues['new-config']['value']);

        // Verify existing config was preserved
        static::assertArrayHasKey('existing-config', $configValues);
        static::assertSame('keep-this', $configValues['existing-config']['value']);
    }

    public function testApplyDoesNothingWhenConfigurationUnchanged(): void
    {
        // Create a test theme with specific configuration
        $initialConfig = [
            'sw-color-brand-primary' => ['value' => '#unchanged'],
            'sw-font-family-base' => ['value' => 'Helvetica'],
        ];
        $testTheme = $this->createTestTheme('Unchanged Theme', $initialConfig);

        $definition = (new ThemeFixtureDefinition('Unchanged Theme'))
            ->config('sw-color-brand-primary', '#unchanged')
            ->config('sw-font-family-base', 'Helvetica');

        // Apply the same configuration
        $this->loader->apply($definition);

        // Verify configuration remains the same
        $updatedTheme = $this->findThemeByName('Unchanged Theme');
        static::assertNotNull($updatedTheme);

        $configValues = $updatedTheme->getConfigValues();
        static::assertNotNull($configValues);

        static::assertSame('#unchanged', $configValues['sw-color-brand-primary']['value']);
        static::assertSame('Helvetica', $configValues['sw-font-family-base']['value']);
    }

    public function testApplyDoesNothingWithEmptyConfiguration(): void
    {
        // Create a test theme with initial configuration
        $initialConfig = [
            'sw-color-brand-primary' => ['value' => '#initial'],
        ];
        $testTheme = $this->createTestTheme('Empty Config Theme', $initialConfig);

        $definition = new ThemeFixtureDefinition('Empty Config Theme');
        // Don't add any configuration

        $this->loader->apply($definition);

        // Verify configuration remains unchanged
        $updatedTheme = $this->findThemeByName('Empty Config Theme');
        static::assertNotNull($updatedTheme);

        $configValues = $updatedTheme->getConfigValues();
        static::assertNotNull($configValues);

        static::assertSame('#initial', $configValues['sw-color-brand-primary']['value']);
    }

    public function testApplyThrowsExceptionForNonExistentTheme(): void
    {
        $definition = (new ThemeFixtureDefinition('Non Existent Theme'))
            ->config('sw-color-brand-primary', '#ff0000');

        $this->expectException(FixtureException::class);
        $this->expectExceptionMessage('Theme "Non Existent Theme" not found');

        $this->loader->apply($definition);
    }

    public function testApplyWithComplexConfiguration(): void
    {
        $testTheme = $this->createTestTheme('Complex Theme');

        $definition = (new ThemeFixtureDefinition('Complex Theme'))
            ->config('sw-color-brand-primary', '#e74c3c')
            ->config('sw-color-brand-secondary', '#3498db')
            ->config('sw-color-success', '#27ae60')
            ->config('sw-color-info', '#17a2b8')
            ->config('sw-color-warning', '#ffc107')
            ->config('sw-color-danger', '#dc3545')
            ->config('sw-font-family-base', 'Roboto, Arial, sans-serif')
            ->config('sw-font-size-base', '16px')
            ->config('sw-border-radius', '4px')
            ->config('custom-boolean-field', true)
            ->config('custom-number-field', 42);

        $this->loader->apply($definition);

        $updatedTheme = $this->findThemeByName('Complex Theme');
        static::assertNotNull($updatedTheme);

        $configValues = $updatedTheme->getConfigValues();
        static::assertNotNull($configValues);

        // Verify all configurations were applied correctly
        static::assertSame('#e74c3c', $configValues['sw-color-brand-primary']['value']);
        static::assertSame('#3498db', $configValues['sw-color-brand-secondary']['value']);
        static::assertSame('#27ae60', $configValues['sw-color-success']['value']);
        static::assertSame('#17a2b8', $configValues['sw-color-info']['value']);
        static::assertSame('#ffc107', $configValues['sw-color-warning']['value']);
        static::assertSame('#dc3545', $configValues['sw-color-danger']['value']);
        static::assertSame('Roboto, Arial, sans-serif', $configValues['sw-font-family-base']['value']);
        static::assertSame('16px', $configValues['sw-font-size-base']['value']);
        static::assertSame('4px', $configValues['sw-border-radius']['value']);
        static::assertTrue($configValues['custom-boolean-field']['value']);
        static::assertSame(42, $configValues['custom-number-field']['value']);
    }

    public function testApplyUpdatesOnlyChangedValues(): void
    {
        // Create theme with mixed initial values
        $initialConfig = [
            'unchanged-value' => ['value' => 'keep-me'],
            'change-me' => ['value' => 'old-value'],
            'remove-me' => ['value' => 'will-stay'], // Won't be removed, just not updated
        ];
        $testTheme = $this->createTestTheme('Partial Update Theme', $initialConfig);

        $definition = (new ThemeFixtureDefinition('Partial Update Theme'))
            ->config('unchanged-value', 'keep-me') // Same value
            ->config('change-me', 'new-value')     // Different value
            ->config('add-me', 'brand-new');       // New value

        $this->loader->apply($definition);

        $updatedTheme = $this->findThemeByName('Partial Update Theme');
        static::assertNotNull($updatedTheme);

        $configValues = $updatedTheme->getConfigValues();
        static::assertNotNull($configValues);

        // Verify unchanged value is still there
        static::assertSame('keep-me', $configValues['unchanged-value']['value']);

        // Verify changed value was updated
        static::assertSame('new-value', $configValues['change-me']['value']);

        // Verify new value was added
        static::assertSame('brand-new', $configValues['add-me']['value']);

        // Verify old value that wasn't in the definition is still there
        static::assertArrayHasKey('remove-me', $configValues);
        static::assertSame('will-stay', $configValues['remove-me']['value']);
    }

    private function createTestTheme(string $name, array $configValues = []): ThemeEntity
    {
        $themeId = Uuid::randomHex();

        $themeData = [
            'id' => $themeId,
            'name' => $name,
            'technicalName' => strtolower(str_replace(' ', '_', $name)),
            'author' => 'Test Author',
            'configValues' => $configValues,
            'active' => true,
            'salesChannels' => [],
        ];

        $this->themeRepository->create([$themeData], $this->context);

        return $this->findThemeByName($name);
    }

    private function findThemeByName(string $name): ?ThemeEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));

        $result = $this->themeRepository->search($criteria, $this->context);

        return $result->first();
    }
}