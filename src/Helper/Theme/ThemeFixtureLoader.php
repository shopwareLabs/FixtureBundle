<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper\Theme;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\FixtureBundle\FixtureException;
use Shopware\Storefront\Theme\ThemeCollection;
use Shopware\Storefront\Theme\ThemeEntity;
use Shopware\Storefront\Theme\ThemeService;

class ThemeFixtureLoader
{
    /**
     * @param EntityRepository<ThemeCollection> $themeRepository
     */
    public function __construct(
        private readonly EntityRepository $themeRepository,
        private readonly ThemeService $themeService
    ) {
    }

    public function apply(ThemeFixtureDefinition $definition): void
    {
        $context = Context::createCLIContext();

        $theme = $this->findThemeByName($definition->getThemeName(), $context);

        if ($theme === null) {
            throw FixtureException::themeNotFound($definition->getThemeName());
        }

        $newConfig = $definition->getConfig();
        if (empty($newConfig)) {
            return;
        }

        $currentConfigValues = $theme->getConfigValues() ?? [];

        if (!$this->hasConfigurationChanged($currentConfigValues, $newConfig)) {
            return; // No changes needed
        }

        $config = array_map(function ($configValue) {
            return [
                'value' => $configValue,
            ];
        }, $newConfig);

        $this->themeService->updateTheme(
            $theme->getId(),
            $config,
            null,
            $context
        );
    }

    private function findThemeByName(string $themeName, Context $context): ?ThemeEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $themeName));

        $themes = $this->themeRepository->search($criteria, $context);

        return $themes->first();
    }

    private function hasConfigurationChanged(array $currentConfigValues, array $newConfig): bool
    {
        foreach ($newConfig as $fieldName => $newValue) {
            $currentFieldConfig = $currentConfigValues[$fieldName] ?? null;

            if ($currentFieldConfig === null) {
                return true;
            }

            $currentValue = $currentFieldConfig['value'] ?? null;

            if ($currentValue !== $newValue) {
                return true;
            }
        }

        return false;
    }
}
