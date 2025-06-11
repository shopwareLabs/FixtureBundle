<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper\CustomField;

use Shopware\Core\Defaults;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldFixtureDefinition
{
    /**
     * @var array<string, string>
     */
    private array $labels = [];

    /**
     * @var array<string, string>
     */
    private array $placeholder = [];

    /**
     * @var array<string, string>
     */
    private array $helpText = [];

    private array $config = [];

    private bool $allowCustomerWrite = false;

    private bool $allowCartExpose = false;

    private bool $storeApiAware = false;

    private int $position = 0;

    /**
     * @param CustomFieldTypes::* $type
     */
    public function __construct(
        private readonly string $name,
        private readonly string $type
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function label(string $translationKey, string $label): self
    {
        $this->labels[$translationKey] = $label;

        return $this;
    }

    public function placeholder(string $translationKey, string $placeholder): self
    {
        $this->placeholder[$translationKey] = $placeholder;

        return $this;
    }

    public function helpText(string $translationKey, string $helpText): self
    {
        $this->helpText[$translationKey] = $helpText;

        return $this;
    }

    public function config(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Allows to be written by the customer in the storefront.
     */
    public function modifiableByCustomer(bool $allow = true): self
    {
        $this->allowCustomerWrite = $allow;

        return $this;
    }

    /**
     * This custom field should be stored inside the cart
     */
    public function availableInCart(bool $allow = true): self
    {
        $this->allowCartExpose = $allow;

        return $this;
    }

    public function visibleInStoreAPI(bool $allow = true): self
    {
        $this->storeApiAware = $allow;

        return $this;
    }

    public function position(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function build(): array
    {
        if ($this->labels === []) {
            $this->labels = [
                'en-GB' => $this->name,
                Defaults::LANGUAGE_SYSTEM => $this->name,
            ];
        }

        return [
            'type' => $this->type,
            'allowCustomerWrite' => $this->allowCustomerWrite,
            'allowCartExpose' => $this->allowCartExpose,
            'storeApiAware' => $this->storeApiAware,
            'config' => array_merge([
                'label' => $this->labels,
                'placeholder' => $this->placeholder,
                'helpText' => $this->helpText,
                'customFieldPosition' => $this->position,
            ], $this->config),
        ];
    }
}
