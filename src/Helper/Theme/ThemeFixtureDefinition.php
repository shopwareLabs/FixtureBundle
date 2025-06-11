<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper\Theme;

class ThemeFixtureDefinition
{
    private array $config = [];

    public function __construct(
        private readonly string $themeName
    ) {
    }

    public function getThemeName(): string
    {
        return $this->themeName;
    }

    public function config(string $name, mixed $value): self
    {
        $this->config[$name] = $value;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
