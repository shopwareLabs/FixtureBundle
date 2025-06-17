<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Tests\Integration\Helper\Customer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\FixtureBundle\Helper\Customer\CustomerFixtureDefinition;
use Shopware\FixtureBundle\Helper\Customer\CustomerFixtureLoader;

class CustomerFixtureLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use DatabaseTransactionBehaviour;

    private CustomerFixtureLoader $loader;
    private EntityRepository $customerRepository;
    private Context $context;

    protected function setUp(): void
    {
        $this->customerRepository = $this->getContainer()->get('customer.repository');
        $this->loader = new CustomerFixtureLoader(
            $this->customerRepository,
            $this->getContainer()->get('customer_group.repository'),
            $this->getContainer()->get('salutation.repository'),
            $this->getContainer()->get('payment_method.repository'),
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get('tag.repository')
        );
        $this->context = Context::createDefaultContext();
    }

    public function testApplyCreatesNewCustomer(): void
    {
        $definition = (new CustomerFixtureDefinition('test@example.com'))
            ->firstName('John')
            ->lastName('Doe')
            ->salutation('mr')
            ->password('password123');

        $this->loader->apply($definition);

        $customer = $this->findCustomerByEmail('test@example.com');

        static::assertInstanceOf(CustomerEntity::class, $customer);
        static::assertSame('test@example.com', $customer->getEmail());
        static::assertSame('John', $customer->getFirstName());
        static::assertSame('Doe', $customer->getLastName());
        static::assertTrue($customer->getActive());
        static::assertFalse($customer->getGuest());
        static::assertNotNull($customer->getCustomerNumber());
        static::assertNotNull($customer->getDefaultBillingAddress());
        static::assertNotNull($customer->getDefaultShippingAddress());
    }

    public function testApplyCreatesCustomerWithCompleteData(): void
    {
        $definition = (new CustomerFixtureDefinition('complete@example.com'))
            ->firstName('Jane')
            ->lastName('Smith')
            ->salutation('mrs')
            ->title('Dr.')
            ->birthday('1990-01-15')
            ->company('ACME Corp')
            ->department('IT Department')
            ->vatId('DE123456789')
            ->password('secure123')
            ->active(true)
            ->guest(false)
            ->customerNumber('CUST-001')
            ->affiliateCode('AFFILIATE123')
            ->campaignCode('CAMPAIGN456')
            ->customFields(['vip_level' => 'gold', 'notes' => 'Important customer']);

        $this->loader->apply($definition);

        $customer = $this->findCustomerByEmail('complete@example.com');

        static::assertNotNull($customer);
        static::assertSame('complete@example.com', $customer->getEmail());
        static::assertSame('Jane', $customer->getFirstName());
        static::assertSame('Smith', $customer->getLastName());
        static::assertSame('Dr.', $customer->getTitle());
        static::assertSame('1990-01-15', $customer->getBirthday()?->format('Y-m-d'));
        static::assertSame('ACME Corp', $customer->getCompany());
        static::assertSame('CUST-001', $customer->getCustomerNumber());
        static::assertSame('AFFILIATE123', $customer->getAffiliateCode());
        static::assertSame('CAMPAIGN456', $customer->getCampaignCode());
        static::assertTrue($customer->getActive());
        static::assertFalse($customer->getGuest());

        $customFields = $customer->getCustomFields();
        static::assertNotNull($customFields);
        static::assertSame('gold', $customFields['vip_level']);
        static::assertSame('Important customer', $customFields['notes']);

        $vatIds = $customer->getVatIds();
        static::assertNotNull($vatIds);
        static::assertContains('DE123456789', $vatIds);
    }

    public function testApplyCreatesCustomerWithAddresses(): void
    {
        $billingAddress = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'street' => '123 Main Street',
            'zipcode' => '12345',
            'city' => 'Springfield',
            'country' => 'DEU',
            'company' => 'ACME Inc',
            'phoneNumber' => '+49 123 456789',
        ];

        $shippingAddress = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'street' => '456 Oak Avenue',
            'zipcode' => '67890',
            'city' => 'Shelbyville',
            'country' => 'DEU',
            'additionalAddressLine1' => 'Building B',
            'additionalAddressLine2' => 'Floor 3',
        ];

        $definition = (new CustomerFixtureDefinition('addresses@example.com'))
            ->firstName('John')
            ->lastName('Doe')
            ->defaultBillingAddress($billingAddress)
            ->defaultShippingAddress($shippingAddress);

        $this->loader->apply($definition);

        $customer = $this->findCustomerByEmail('addresses@example.com');

        static::assertNotNull($customer);

        $defaultBilling = $customer->getDefaultBillingAddress();
        static::assertNotNull($defaultBilling);
        static::assertSame('123 Main Street', $defaultBilling->getStreet());
        static::assertSame('12345', $defaultBilling->getZipcode());
        static::assertSame('Springfield', $defaultBilling->getCity());
        static::assertSame('ACME Inc', $defaultBilling->getCompany());
        static::assertSame('+49 123 456789', $defaultBilling->getPhoneNumber());

        $defaultShipping = $customer->getDefaultShippingAddress();
        static::assertNotNull($defaultShipping);
        static::assertSame('456 Oak Avenue', $defaultShipping->getStreet());
        static::assertSame('67890', $defaultShipping->getZipcode());
        static::assertSame('Shelbyville', $defaultShipping->getCity());
        static::assertSame('Building B', $defaultShipping->getAdditionalAddressLine1());
        static::assertSame('Floor 3', $defaultShipping->getAdditionalAddressLine2());

        // Verify that billing and shipping are different addresses
        static::assertNotSame($defaultBilling->getId(), $defaultShipping->getId());
    }

    public function testApplyCreatesSameBillingAndShippingAddress(): void
    {
        $address = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'street' => '123 Same Street',
            'zipcode' => '11111',
            'city' => 'Same City',
            'country' => 'DEU',
        ];

        $definition = (new CustomerFixtureDefinition('same-address@example.com'))
            ->firstName('John')
            ->lastName('Doe')
            ->defaultBillingAddress($address)
            ->defaultShippingAddress($address);

        $this->loader->apply($definition);

        $customer = $this->findCustomerByEmail('same-address@example.com');

        static::assertNotNull($customer);

        $defaultBilling = $customer->getDefaultBillingAddress();
        $defaultShipping = $customer->getDefaultShippingAddress();

        static::assertNotNull($defaultBilling);
        static::assertNotNull($defaultShipping);

        // Should use the same address ID when addresses are identical
        static::assertSame($defaultBilling->getId(), $defaultShipping->getId());
        static::assertSame('123 Same Street', $defaultBilling->getStreet());
        static::assertSame('11111', $defaultBilling->getZipcode());
        static::assertSame('Same City', $defaultBilling->getCity());
    }

    public function testApplyCreatesCustomerWithAdditionalAddresses(): void
    {
        $definition = (new CustomerFixtureDefinition('multi-address@example.com'))
            ->firstName('John')
            ->lastName('Doe')
            ->addAddress('work', [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'street' => '789 Work Street',
                'zipcode' => '99999',
                'city' => 'Work City',
                'country' => 'DEU',
                'company' => 'Work Corp',
            ])
            ->addAddress('vacation', [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'street' => '321 Beach Road',
                'zipcode' => '88888',
                'city' => 'Beach Town',
                'country' => 'DEU',
            ]);

        $this->loader->apply($definition);

        $customer = $this->findCustomerByEmail('multi-address@example.com');

        static::assertNotNull($customer);

        $addresses = $customer->getAddresses();
        static::assertNotNull($addresses);

        // Should have 3 addresses total: default billing/shipping + 2 additional
        static::assertGreaterThanOrEqual(3, $addresses->count());

        $addressStreets = [];
        foreach ($addresses as $address) {
            $addressStreets[] = $address->getStreet();
        }

        static::assertContains('789 Work Street', $addressStreets);
        static::assertContains('321 Beach Road', $addressStreets);
    }

    public function testApplyUpdatesExistingCustomer(): void
    {
        // First create a customer
        $definition = (new CustomerFixtureDefinition('update@example.com'))
            ->firstName('Original')
            ->lastName('Name')
            ->company('Original Company');

        $this->loader->apply($definition);

        $originalCustomer = $this->findCustomerByEmail('update@example.com');
        static::assertNotNull($originalCustomer);
        $originalId = $originalCustomer->getId();

        // Now update the same customer
        $updatedDefinition = (new CustomerFixtureDefinition('update@example.com'))
            ->firstName('Updated')
            ->lastName('Name')
            ->company('Updated Company')
            ->title('Mr.')
            ->customFields(['updated' => true]);

        $this->loader->apply($updatedDefinition);

        $updatedCustomer = $this->findCustomerByEmail('update@example.com');

        static::assertNotNull($updatedCustomer);
        static::assertSame($originalId, $updatedCustomer->getId()); // Same ID = updated, not new
        static::assertSame('Updated', $updatedCustomer->getFirstName());
        static::assertSame('Updated Company', $updatedCustomer->getCompany());
        static::assertSame('Mr.', $updatedCustomer->getTitle());

        $customFields = $updatedCustomer->getCustomFields();
        static::assertNotNull($customFields);
        static::assertTrue($customFields['updated']);
    }

    public function testApplyCreatesGuestCustomer(): void
    {
        $definition = (new CustomerFixtureDefinition('guest@example.com'))
            ->firstName('Guest')
            ->lastName('User')
            ->guest(true)
            ->active(false);

        $this->loader->apply($definition);

        $customer = $this->findCustomerByEmail('guest@example.com');

        static::assertNotNull($customer);
        static::assertSame('Guest', $customer->getFirstName());
        static::assertSame('User', $customer->getLastName());
        static::assertTrue($customer->getGuest());
        static::assertFalse($customer->getActive());
    }

    public function testApplyCreatesCustomerWithDefaultValues(): void
    {
        // Minimal definition to test defaults
        $definition = new CustomerFixtureDefinition('minimal@example.com');

        $this->loader->apply($definition);

        $customer = $this->findCustomerByEmail('minimal@example.com');

        static::assertNotNull($customer);
        static::assertSame('minimal@example.com', $customer->getEmail());
        static::assertSame('Test', $customer->getFirstName()); // Default first name
        static::assertSame('Customer', $customer->getLastName()); // Default last name
        static::assertTrue($customer->getActive()); // Default active
        static::assertFalse($customer->getGuest()); // Default not guest
        static::assertNotNull($customer->getCustomerNumber());
        static::assertNotNull($customer->getDefaultBillingAddress());
        static::assertNotNull($customer->getDefaultShippingAddress());
    }

    public function testApplyUpdatesCustomerWithNewAddresses(): void
    {
        // Create customer with initial address
        $definition = (new CustomerFixtureDefinition('address-update@example.com'))
            ->firstName('John')
            ->lastName('Doe')
            ->defaultBillingAddress([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'street' => 'Old Street 1',
                'zipcode' => '11111',
                'city' => 'Old City',
                'country' => 'DEU',
            ]);

        $this->loader->apply($definition);

        $originalCustomer = $this->findCustomerByEmail('address-update@example.com');
        static::assertNotNull($originalCustomer);
        static::assertSame('Old Street 1', $originalCustomer->getDefaultBillingAddress()->getStreet());

        // Update with new address
        $updatedDefinition = (new CustomerFixtureDefinition('address-update@example.com'))
            ->firstName('John')
            ->lastName('Doe')
            ->defaultBillingAddress([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'street' => 'New Street 2',
                'zipcode' => '22222',
                'city' => 'New City',
                'country' => 'DEU',
            ]);

        $this->loader->apply($updatedDefinition);

        $updatedCustomer = $this->findCustomerByEmail('address-update@example.com');

        static::assertNotNull($updatedCustomer);
        static::assertSame($originalCustomer->getId(), $updatedCustomer->getId());

        $billingAddress = $updatedCustomer->getDefaultBillingAddress();
        static::assertNotNull($billingAddress);
        static::assertSame('New Street 2', $billingAddress->getStreet());
        static::assertSame('22222', $billingAddress->getZipcode());
        static::assertSame('New City', $billingAddress->getCity());
    }

    private function findCustomerByEmail(string $email): ?CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addAssociation('defaultBillingAddress');
        $criteria->addAssociation('defaultShippingAddress');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('tags');

        $result = $this->customerRepository->search($criteria, $this->context);

        return $result->first();
    }
}