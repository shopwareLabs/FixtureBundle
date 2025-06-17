<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Tests\Integration\Helper\CustomField;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\FixtureBundle\Helper\CustomField\CustomFieldFixtureDefinition;
use Shopware\FixtureBundle\Helper\CustomField\CustomFieldSetFixtureDefinition;
use Shopware\FixtureBundle\Helper\CustomField\CustomFieldSetFixtureLoader;

class CustomFieldSetFixtureLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use DatabaseTransactionBehaviour;

    private CustomFieldSetFixtureLoader $loader;
    private EntityRepository $customFieldSetRepository;
    private EntityRepository $customFieldRepository;
    private Context $context;

    protected function setUp(): void
    {
        $this->customFieldSetRepository = $this->getContainer()->get('custom_field_set.repository');
        $this->customFieldRepository = $this->getContainer()->get('custom_field.repository');
        $this->loader = new CustomFieldSetFixtureLoader(
            $this->customFieldSetRepository,
            $this->customFieldRepository
        );
        $this->context = Context::createDefaultContext();
    }

    public function testApplyCreatesNewCustomFieldSet(): void
    {
        $definition = new CustomFieldSetFixtureDefinition(
            'Test Custom Fields',
            'test_custom_fields'
        );

        $definition
            ->label('en-GB', 'Test Custom Fields')
            ->label('de-DE', 'Test Custom Fields DE')
            ->relation('product');

        $this->loader->apply($definition);

        $customFieldSet = $this->findCustomFieldSetByName('test_custom_fields');

        static::assertInstanceOf(CustomFieldSetEntity::class, $customFieldSet);
        static::assertSame('test_custom_fields', $customFieldSet->getName());
        static::assertSame('Test Custom Fields', $customFieldSet->getConfig()['label']['en-GB']);
        static::assertSame('Test Custom Fields DE', $customFieldSet->getConfig()['label']['de-DE']);
        static::assertCount(1, $customFieldSet->getRelations());
        static::assertSame('product', $customFieldSet->getRelations()->first()->getEntityName());
    }

    public function testApplyCreatesCustomFields(): void
    {
        $definition = new CustomFieldSetFixtureDefinition(
            'Product Extra Fields',
            'product_extra_fields'
        );

        $textField = (new CustomFieldFixtureDefinition('description', CustomFieldTypes::TEXT))
            ->label('en-GB', 'Extra Description')
            ->placeholder('en-GB', 'Enter description...')
            ->position(1)
            ->visibleInStoreAPI(true);

        $boolField = (new CustomFieldFixtureDefinition('featured', CustomFieldTypes::BOOL))
            ->label('en-GB', 'Featured Product')
            ->position(2)
            ->modifiableByCustomer(false);

        $selectField = (new CustomFieldFixtureDefinition('priority', CustomFieldTypes::SELECT))
            ->label('en-GB', 'Priority Level')
            ->config([
                'options' => [
                    ['value' => 'low', 'label' => ['en-GB' => 'Low']],
                    ['value' => 'high', 'label' => ['en-GB' => 'High']],
                ],
            ])
            ->position(3);

        $definition
            ->field($textField)
            ->field($boolField)
            ->field($selectField);

        $this->loader->apply($definition);

        $customFieldSet = $this->findCustomFieldSetByName('product_extra_fields');
        static::assertNotNull($customFieldSet);

        $customFields = $this->findCustomFieldsBySetId($customFieldSet->getId());

        static::assertCount(3, $customFields);

        $fieldsByName = [];
        foreach ($customFields as $field) {
            $fieldsByName[$field->getName()] = $field;
        }

        // Test text field
        static::assertArrayHasKey('product_extra_fields_description', $fieldsByName);
        $textFieldEntity = $fieldsByName['product_extra_fields_description'];
        static::assertSame(CustomFieldTypes::TEXT, $textFieldEntity->getType());
        static::assertSame('Extra Description', $textFieldEntity->getConfig()['label']['en-GB']);
        static::assertSame('Enter description...', $textFieldEntity->getConfig()['placeholder']['en-GB']);
        static::assertSame(1, $textFieldEntity->getConfig()['customFieldPosition']);

        // Test bool field
        static::assertArrayHasKey('product_extra_fields_featured', $fieldsByName);
        $boolFieldEntity = $fieldsByName['product_extra_fields_featured'];
        static::assertSame(CustomFieldTypes::BOOL, $boolFieldEntity->getType());
        static::assertSame('Featured Product', $boolFieldEntity->getConfig()['label']['en-GB']);
        static::assertSame(2, $boolFieldEntity->getConfig()['customFieldPosition']);
        static::assertFalse($boolFieldEntity->isAllowCustomerWrite());

        // Test select field
        static::assertArrayHasKey('product_extra_fields_priority', $fieldsByName);
        $selectFieldEntity = $fieldsByName['product_extra_fields_priority'];
        static::assertSame(CustomFieldTypes::SELECT, $selectFieldEntity->getType());
        static::assertSame('Priority Level', $selectFieldEntity->getConfig()['label']['en-GB']);
        static::assertSame(3, $selectFieldEntity->getConfig()['customFieldPosition']);
        static::assertCount(2, $selectFieldEntity->getConfig()['options']);
    }

    public function testApplyUpdatesExistingCustomFieldSet(): void
    {
        // First create a custom field set
        $definition = new CustomFieldSetFixtureDefinition(
            'Original Name',
            'update_test_set'
        );

        $textField = (new CustomFieldFixtureDefinition('original_field', CustomFieldTypes::TEXT))
            ->label('en-GB', 'Original Field');

        $definition->field($textField);

        $this->loader->apply($definition);

        $originalSet = $this->findCustomFieldSetByName('update_test_set');
        static::assertNotNull($originalSet);
        $originalSetId = $originalSet->getId();

        // Now update with a different label and additional field
        $updatedDefinition = new CustomFieldSetFixtureDefinition(
            'Updated Name',
            'update_test_set'
        );

        $updatedTextField = (new CustomFieldFixtureDefinition('original_field', CustomFieldTypes::TEXT))
            ->label('en-GB', 'Updated Field Label');

        $newField = (new CustomFieldFixtureDefinition('new_field', CustomFieldTypes::INT))
            ->label('en-GB', 'New Field');

        $updatedDefinition
            ->field($updatedTextField)
            ->field($newField);

        $this->loader->apply($updatedDefinition);

        $updatedSet = $this->findCustomFieldSetByName('update_test_set');
        static::assertNotNull($updatedSet);
        static::assertSame($originalSetId, $updatedSet->getId());

        $customFields = $this->findCustomFieldsBySetId($updatedSet->getId());
        static::assertCount(2, $customFields);

        $fieldsByName = [];
        foreach ($customFields as $field) {
            $fieldsByName[$field->getName()] = $field;
        }

        static::assertArrayHasKey('update_test_set_original_field', $fieldsByName);
        static::assertArrayHasKey('update_test_set_new_field', $fieldsByName);
        static::assertSame('Updated Field Label', $fieldsByName['update_test_set_original_field']->getConfig()['label']['en-GB']);
        static::assertSame('New Field', $fieldsByName['update_test_set_new_field']->getConfig()['label']['en-GB']);
    }

    public function testApplyDeletesRemovedFields(): void
    {
        // Create a custom field set with multiple fields
        $definition = new CustomFieldSetFixtureDefinition(
            'Delete Test Set',
            'delete_test_set'
        );

        $field1 = (new CustomFieldFixtureDefinition('field1', CustomFieldTypes::TEXT))
            ->label('en-GB', 'Field 1');

        $field2 = (new CustomFieldFixtureDefinition('field2', CustomFieldTypes::TEXT))
            ->label('en-GB', 'Field 2');

        $field3 = (new CustomFieldFixtureDefinition('field3', CustomFieldTypes::TEXT))
            ->label('en-GB', 'Field 3');

        $definition
            ->field($field1)
            ->field($field2)
            ->field($field3);

        $this->loader->apply($definition);

        $customFieldSet = $this->findCustomFieldSetByName('delete_test_set');
        static::assertNotNull($customFieldSet);

        $originalFields = $this->findCustomFieldsBySetId($customFieldSet->getId());
        static::assertCount(3, $originalFields);

        // Now apply definition with only field1 and field3 (removing field2)
        $updatedDefinition = new CustomFieldSetFixtureDefinition(
            'Delete Test Set',
            'delete_test_set'
        );

        $updatedDefinition
            ->field($field1)
            ->field($field3);

        $this->loader->apply($updatedDefinition);

        $updatedFields = $this->findCustomFieldsBySetId($customFieldSet->getId());
        static::assertCount(2, $updatedFields);

        $fieldNames = array_map(fn(CustomFieldEntity $field) => $field->getName(), $updatedFields);
        static::assertContains('delete_test_set_field1', $fieldNames);
        static::assertContains('delete_test_set_field3', $fieldNames);
        static::assertNotContains('delete_test_set_field2', $fieldNames);
    }

    public function testApplyWithMultipleRelations(): void
    {
        $definition = new CustomFieldSetFixtureDefinition(
            'Multi Relation Set',
            'multi_relation_set'
        );

        $definition
            ->relation('product')
            ->relation('customer')
            ->relation('order');

        $this->loader->apply($definition);

        $customFieldSet = $this->findCustomFieldSetByName('multi_relation_set');
        static::assertNotNull($customFieldSet);
        static::assertCount(3, $customFieldSet->getRelations());

        $relationEntityNames = [];
        foreach ($customFieldSet->getRelations() as $relation) {
            $relationEntityNames[] = $relation->getEntityName();
        }

        static::assertContains('product', $relationEntityNames);
        static::assertContains('customer', $relationEntityNames);
        static::assertContains('order', $relationEntityNames);
    }

    private function findCustomFieldSetByName(string $name): ?CustomFieldSetEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $criteria->addAssociation('relations');

        $result = $this->customFieldSetRepository->search($criteria, $this->context);

        return $result->first();
    }

    /**
     * @return CustomFieldEntity[]
     */
    private function findCustomFieldsBySetId(string $customFieldSetId): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFieldSetId', $customFieldSetId));

        $result = $this->customFieldRepository->search($criteria, $this->context);

        return $result->getElements();
    }
}