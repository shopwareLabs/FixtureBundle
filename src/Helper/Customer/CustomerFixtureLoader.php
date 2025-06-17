<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper\Customer;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\Tag\TagCollection;
use Shopware\Core\Test\TestDefaults;

class CustomerFixtureLoader
{
    /**
     * @param EntityRepository<CustomerCollection> $customerRepository
     * @param EntityRepository<CustomerGroupCollection> $customerGroupRepository
     * @param EntityRepository<SalutationCollection> $salutationRepository
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodRepository
     * @param EntityRepository<LanguageCollection> $languageRepository
     * @param EntityRepository<CountryCollection> $countryRepository
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     * @param EntityRepository<TagCollection> $tagRepository
     */
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $customerGroupRepository,
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly EntityRepository $languageRepository,
        private readonly EntityRepository $countryRepository,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $tagRepository
    ) {
    }

    public function apply(CustomerFixtureDefinition $definition): void
    {
        $context = Context::createCLIContext();
        $context->addState(Context::SKIP_TRIGGER_FLOW);

        $customer = $this->findCustomerByEmail($definition->getEmail(), $context);

        if ($customer) {
            $this->updateCustomer($customer->getId(), $definition, $context);
        } else {
            $this->createCustomer($definition, $context);
        }
    }

    private function findCustomerByEmail(string $email, Context $context): ?CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addAssociation('defaultBillingAddress');
        $criteria->addAssociation('defaultShippingAddress');
        $criteria->addAssociation('addresses');

        $result = $this->customerRepository->search($criteria, $context);

        return $result->first();
    }

    private function createCustomer(CustomerFixtureDefinition $definition, Context $context): void
    {
        $data = $this->buildCustomerData($definition, $context);
        $data['id'] = Uuid::randomHex();

        $this->customerRepository->create([$data], $context);
    }

    private function updateCustomer(string $customerId, CustomerFixtureDefinition $definition, Context $context): void
    {
        // Load existing customer with addresses to check for existing addresses
        $criteria = new Criteria([$customerId]);
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('defaultBillingAddress');
        $criteria->addAssociation('defaultShippingAddress');

        $existingCustomer = $this->customerRepository->search($criteria, $context)->first();

        $data = $this->buildCustomerData($definition, $context, $existingCustomer);
        $data['id'] = $customerId;

        $this->customerRepository->update([$data], $context);
    }

    private function buildCustomerData(CustomerFixtureDefinition $definition, Context $context, ?CustomerEntity $existingCustomer = null): array
    {
        $data = [];

        if ($definition->getCustomerNumber() !== null) {
            $data['customerNumber'] = $definition->getCustomerNumber();
        } else {
            $data['customerNumber'] = Uuid::randomHex();
        }

        $data['firstName'] = $definition->getFirstName() ?? 'Test';
        $data['lastName'] = $definition->getLastName() ?? 'Customer';

        $data['email'] = $definition->getEmail();

        if ($definition->getPassword() !== null) {
            $data['password'] = $definition->getPassword();
        }

        $data['active'] = $definition->isActive();
        $data['guest'] = $definition->isGuest();
        $data['accountMode'] = $definition->isAccountMode();

        if ($definition->getTitle() !== null) {
            $data['title'] = $definition->getTitle();
        }

        if ($definition->getBirthday() !== null) {
            $data['birthday'] = $definition->getBirthday();
        }

        if ($definition->getCompany() !== null) {
            $data['company'] = $definition->getCompany();
        }

        if ($definition->getDepartment() !== null) {
            $data['company'] = $definition->getDepartment();
        }

        if ($definition->getVatId() !== null) {
            $data['vatIds'] = [$definition->getVatId()];
        }

        if ($definition->getAffiliateCode() !== null) {
            $data['affiliateCode'] = $definition->getAffiliateCode();
        }

        if ($definition->getCampaignCode() !== null) {
            $data['campaignCode'] = $definition->getCampaignCode();
        }

        if (!empty($definition->getCustomFields())) {
            $data['customFields'] = $definition->getCustomFields();
        }

        if ($definition->getSalesChannelId() !== null) {
            $data['salesChannelId'] = $definition->getSalesChannelId();
        }

        // Handle salutation
        if ($definition->getSalutationKey() !== null) {
            $salutationId = $this->getSalutationId($definition->getSalutationKey(), $context);
            if ($salutationId) {
                $data['salutationId'] = $salutationId;
            }
        }

        // Handle customer group
        if ($definition->getCustomerGroup() !== null) {
            $customerGroupId = $this->getCustomerGroupId($definition->getCustomerGroup(), $context);
            if ($customerGroupId) {
                $data['groupId'] = $customerGroupId;
            }
        } else {
            // Use default customer group from sales channel
            $salesChannelId = $data['salesChannelId'] ?? TestDefaults::SALES_CHANNEL;
            $defaultCustomerGroupId = $this->getDefaultCustomerGroupId($salesChannelId, $context);
            if ($defaultCustomerGroupId) {
                $data['groupId'] = $defaultCustomerGroupId;
            }
        }

        // Handle requested customer group
        if ($definition->getRequestedCustomerGroupKey() !== null) {
            $requestedGroupId = $this->getCustomerGroupId($definition->getRequestedCustomerGroupKey(), $context);
            if ($requestedGroupId) {
                $data['requestedGroupId'] = $requestedGroupId;
            }
        }

        // Handle payment method
        if ($definition->getDefaultPaymentMethod() !== null) {
            $paymentMethodId = $this->getPaymentMethodId($definition->getDefaultPaymentMethod(), $context);
            if ($paymentMethodId) {
                $data['defaultPaymentMethodId'] = $paymentMethodId;
            }
        }

        // Handle language
        if ($definition->getLanguageKey() !== null) {
            $languageId = $this->getLanguageId($definition->getLanguageKey(), $context);
            if ($languageId) {
                $data['languageId'] = $languageId;
            }
        }

        // Handle sales channel
        if ($definition->getSalesChannelId() !== null) {
            $data['salesChannelId'] = $definition->getSalesChannelId();
        } else {
            $data['salesChannelId'] = TestDefaults::SALES_CHANNEL;
        }

        // Handle addresses
        $addresses = [];
        $isUpdate = $existingCustomer !== null;

        // Handle billing address
        if ($definition->getDefaultBillingAddress() !== null) {
            $billingAddressData = $definition->getDefaultBillingAddress();

            if ($isUpdate) {
                // Check if this address already exists
                $existingBillingId = $this->findExistingAddress($existingCustomer, $billingAddressData, $context);
                if ($existingBillingId) {
                    $data['defaultBillingAddressId'] = $existingBillingId;
                } else {
                    // Create new address
                    $billingAddress = $this->buildAddressData($billingAddressData, $context);
                    $billingAddress['id'] = Uuid::randomHex();
                    $data['defaultBillingAddressId'] = $billingAddress['id'];
                    $addresses[] = $billingAddress;
                }
            } else {
                // Create new address for new customer
                $billingAddress = $this->buildAddressData($billingAddressData, $context);
                $billingAddress['id'] = Uuid::randomHex();
                $data['defaultBillingAddressId'] = $billingAddress['id'];
                $addresses[] = $billingAddress;
            }
        } elseif (!$isUpdate) {
            // Only create default address for new customers
            $billingAddress = $this->buildAddressData([
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
            ], $context);
            $billingAddress['id'] = Uuid::randomHex();
            $data['defaultBillingAddressId'] = $billingAddress['id'];
            $addresses[] = $billingAddress;
        }

        // Handle shipping address
        if ($definition->getDefaultShippingAddress() !== null) {
            $shippingAddressData = $definition->getDefaultShippingAddress();

            if ($isUpdate) {
                // Check if this address already exists
                $existingShippingId = $this->findExistingAddress($existingCustomer, $shippingAddressData, $context);
                if ($existingShippingId) {
                    $data['defaultShippingAddressId'] = $existingShippingId;
                } elseif (isset($data['defaultBillingAddressId'])
                          && $definition->getDefaultBillingAddress() !== null
                          && $this->areAddressesSame($definition->getDefaultBillingAddress(), $shippingAddressData)) {
                    // Use the same as billing if they're the same
                    $data['defaultShippingAddressId'] = $data['defaultBillingAddressId'];
                } else {
                    // Create new address
                    $shippingAddress = $this->buildAddressData($shippingAddressData, $context);
                    $shippingAddress['id'] = Uuid::randomHex();
                    $data['defaultShippingAddressId'] = $shippingAddress['id'];
                    $addresses[] = $shippingAddress;
                }
            } else {
                // Handle for new customers
                if ($definition->getDefaultBillingAddress() !== null
                    && $this->areAddressesSame($definition->getDefaultBillingAddress(), $shippingAddressData)) {
                    $data['defaultShippingAddressId'] = $data['defaultBillingAddressId'];
                } else {
                    $shippingAddress = $this->buildAddressData($shippingAddressData, $context);
                    $shippingAddress['id'] = Uuid::randomHex();
                    $data['defaultShippingAddressId'] = $shippingAddress['id'];
                    $addresses[] = $shippingAddress;
                }
            }
        } elseif (!$isUpdate && isset($data['defaultBillingAddressId'])) {
            // Use billing as shipping for new customers if not specified
            $data['defaultShippingAddressId'] = $data['defaultBillingAddressId'];
        }

        foreach ($definition->getAdditionalAddresses() as $addressData) {
            $address = $this->buildAddressData($addressData, $context);
            $address['id'] = Uuid::randomHex();
            $addresses[] = $address;
        }

        if (!empty($addresses)) {
            $data['addresses'] = $addresses;
        }

        // Handle tags
        if (!empty($definition->getTags())) {
            $tagIds = $this->getTagIds($definition->getTags(), $context);
            if (!empty($tagIds)) {
                $data['tags'] = array_map(function ($tagId) {
                    return ['id' => $tagId];
                }, $tagIds);
            }
        }

        return $data;
    }

    private function buildAddressData(array $addressData, Context $context): array
    {
        $address = [
            'firstName' => $addressData['firstName'] ?? 'First',
            'lastName' => $addressData['lastName'] ?? 'Last',
            'street' => $addressData['street'] ?? 'Example Street 1',
            'zipcode' => $addressData['zipcode'] ?? '12345',
            'city' => $addressData['city'] ?? 'Example City',
        ];

        if (isset($addressData['salutation'])) {
            $salutationId = $this->getSalutationId($addressData['salutation'], $context);
            if ($salutationId) {
                $address['salutationId'] = $salutationId;
            }
        }

        if (isset($addressData['title'])) {
            $address['title'] = $addressData['title'];
        }

        if (isset($addressData['company'])) {
            $address['company'] = $addressData['company'];
        }

        if (isset($addressData['department'])) {
            $address['department'] = $addressData['department'];
        }

        if (isset($addressData['vatId'])) {
            $address['vatId'] = $addressData['vatId'];
        }

        if (isset($addressData['phoneNumber'])) {
            $address['phoneNumber'] = $addressData['phoneNumber'];
        }

        if (isset($addressData['additionalAddressLine1'])) {
            $address['additionalAddressLine1'] = $addressData['additionalAddressLine1'];
        }

        if (isset($addressData['additionalAddressLine2'])) {
            $address['additionalAddressLine2'] = $addressData['additionalAddressLine2'];
        }

        if (isset($addressData['country'])) {
            $countryId = $this->getCountryId($addressData['country'], $context);
            if ($countryId) {
                $address['countryId'] = $countryId;
            }
        } else {
            // Get default country from sales channel
            $address['countryId'] = $this->getDefaultCountryId($context);
        }

        if (isset($addressData['customFields'])) {
            $address['customFields'] = $addressData['customFields'];
        }

        return $address;
    }

    private function areAddressesSame(array $address1, array $address2): bool
    {
        $compareFields = ['firstName', 'lastName', 'street', 'zipcode', 'city', 'country', 'company', 'department'];

        foreach ($compareFields as $field) {
            if (($address1[$field] ?? null) !== ($address2[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function getSalutationId(string $salutationKey, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));

        $result = $this->salutationRepository->search($criteria, $context);
        /** @var SalutationEntity|null $salutation */
        $salutation = $result->first();

        return $salutation?->getId();
    }

    private function getCustomerGroupId(string $name, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));

        $result = $this->customerGroupRepository->search($criteria, $context);
        if ($result->getTotal() > 0) {
            return $result->first()->getId();
        }

        return null;
    }

    private function getPaymentMethodId(string $key, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $key));

        $result = $this->paymentMethodRepository->search($criteria, $context);
        if ($result->getTotal() > 0) {
            return $result->first()->getId();
        }

        return null;
    }

    private function getLanguageId(string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('translationCode.code', $code));

        $result = $this->languageRepository->search($criteria, $context);
        /** @var LanguageEntity|null $language */
        $language = $result->first();

        return $language?->getId();
    }

    private function getCountryId(string $iso3, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso3', $iso3));

        $result = $this->countryRepository->search($criteria, $context);
        /** @var CountryEntity|null $country */
        $country = $result->first();

        return $country?->getId();
    }

    private function getTagIds(array $tagNames, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', $tagNames));

        return $this->tagRepository->searchIds($criteria, $context)->getIds();
    }

    private function getDefaultCountryId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso3', 'DEU'));

        $result = $this->countryRepository->search($criteria, $context);
        /** @var CountryEntity|null $country */
        $country = $result->first();

        if ($country) {
            return $country->getId();
        }

        // Fallback to any country
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $result = $this->countryRepository->search($criteria, $context);
        /** @var CountryEntity $country */
        $country = $result->first();

        return $country->getId();
    }

    private function getDefaultCustomerGroupId(string $salesChannelId, Context $context): ?string
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation('customerGroup');

        $result = $this->salesChannelRepository->search($criteria, $context);

        if ($result->getTotal() === 0) {
            return null;
        }

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $result->first();

        return $salesChannel->getCustomerGroupId();
    }

    private function findExistingAddress(CustomerEntity $customer, array $addressData, Context $context): ?string
    {
        $addresses = $customer->getAddresses();
        if (!$addresses) {
            return null;
        }

        $compareData = $this->buildAddressData($addressData, $context);

        foreach ($addresses as $address) {
            $matches = true;

            // Compare important fields
            $fieldsToCompare = ['firstName', 'lastName', 'street', 'zipcode', 'city', 'countryId', 'company', 'department'];

            foreach ($fieldsToCompare as $field) {
                $addressValue = $address->get($field);
                $compareValue = $compareData[$field] ?? null;

                if ($addressValue !== $compareValue) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                return $address->getId();
            }
        }

        return null;
    }
}
