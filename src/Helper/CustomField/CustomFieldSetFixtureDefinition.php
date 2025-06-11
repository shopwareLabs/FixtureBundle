<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper\CustomField;

class CustomFieldSetFixtureDefinition
{
    /**
     * @var array<string, CustomFieldFixtureDefinition>
     */
    private array $fields = [];

    private array $relations = [];

    private array $labels = [];

    public function __construct(
        private readonly string $name,
        private readonly string $technicalName
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function label(string $translationKey, string $label): self
    {
        $this->labels[$translationKey] = $label;

        return $this;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function field(CustomFieldFixtureDefinition $definition): self
    {
        $this->fields[$definition->getName()] = $definition;

        return $this;
    }

    /**
     * @return array<string, CustomFieldFixtureDefinition>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function relation(string $entityName): self
    {
        $this->relations[] = $entityName;

        return $this;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }
}
