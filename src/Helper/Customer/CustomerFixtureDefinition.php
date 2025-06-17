<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper\Customer;

class CustomerFixtureDefinition
{
    private ?string $customerNumber = null;

    private ?string $firstName = null;

    private ?string $lastName = null;

    private ?string $salutationKey = null;

    private ?string $title = null;

    private ?string $birthday = null;

    private ?string $customerGroup = null;

    private ?string $defaultPaymentMethod = null;

    private ?string $languageKey = null;

    private string $email;

    private bool $active = true;

    private bool $guest = false;

    private bool $accountMode = false;

    private ?string $company = null;

    private ?string $department = null;

    private ?string $vatId = null;

    private ?array $defaultBillingAddress = null;

    private ?array $defaultShippingAddress = null;

    private array $additionalAddresses = [];

    private array $customFields = [];

    private array $tags = [];

    private ?string $salesChannelId = null;

    private ?string $requestedCustomerGroupKey = null;

    private ?string $affiliateCode = null;

    private ?string $campaignCode = null;

    private ?string $password = null;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function customerNumber(string $customerNumber): self
    {
        $this->customerNumber = $customerNumber;

        return $this;
    }

    public function getCustomerNumber(): ?string
    {
        return $this->customerNumber;
    }

    public function firstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function salutation(string $salutationKey): self
    {
        $this->salutationKey = $salutationKey;

        return $this;
    }

    public function getSalutationKey(): ?string
    {
        return $this->salutationKey;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function birthday(string $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function customerGroup(string $customerGroup): self
    {
        $this->customerGroup = $customerGroup;

        return $this;
    }

    public function getCustomerGroup(): ?string
    {
        return $this->customerGroup;
    }

    public function defaultPaymentMethod(string $defaultPaymentMethod): self
    {
        $this->defaultPaymentMethod = $defaultPaymentMethod;

        return $this;
    }

    public function getDefaultPaymentMethod(): ?string
    {
        return $this->defaultPaymentMethod;
    }

    public function language(string $languageKey): self
    {
        $this->languageKey = $languageKey;

        return $this;
    }

    public function getLanguageKey(): ?string
    {
        return $this->languageKey;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function active(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function guest(bool $guest): self
    {
        $this->guest = $guest;

        return $this;
    }

    public function isGuest(): bool
    {
        return $this->guest;
    }

    public function accountMode(bool $accountMode): self
    {
        $this->accountMode = $accountMode;

        return $this;
    }

    public function isAccountMode(): bool
    {
        return $this->accountMode;
    }

    public function company(string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function department(string $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function vatId(string $vatId): self
    {
        $this->vatId = $vatId;

        return $this;
    }

    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    public function defaultBillingAddress(array $address): self
    {
        $this->defaultBillingAddress = $address;

        return $this;
    }

    public function getDefaultBillingAddress(): ?array
    {
        return $this->defaultBillingAddress;
    }

    public function defaultShippingAddress(array $address): self
    {
        $this->defaultShippingAddress = $address;

        return $this;
    }

    public function getDefaultShippingAddress(): ?array
    {
        return $this->defaultShippingAddress;
    }

    public function addAddress(string $key, array $address): self
    {
        $this->additionalAddresses[$key] = $address;

        return $this;
    }

    public function getAdditionalAddresses(): array
    {
        return $this->additionalAddresses;
    }

    public function customFields(array $customFields): self
    {
        $this->customFields = $customFields;

        return $this;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function tags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function salesChannelId(string $salesChannelId): self
    {
        $this->salesChannelId = $salesChannelId;

        return $this;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function requestedCustomerGroup(string $requestedCustomerGroupKey): self
    {
        $this->requestedCustomerGroupKey = $requestedCustomerGroupKey;

        return $this;
    }

    public function getRequestedCustomerGroupKey(): ?string
    {
        return $this->requestedCustomerGroupKey;
    }

    public function affiliateCode(string $affiliateCode): self
    {
        $this->affiliateCode = $affiliateCode;

        return $this;
    }

    public function getAffiliateCode(): ?string
    {
        return $this->affiliateCode;
    }

    public function campaignCode(string $campaignCode): self
    {
        $this->campaignCode = $campaignCode;

        return $this;
    }

    public function getCampaignCode(): ?string
    {
        return $this->campaignCode;
    }

    public function password(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
