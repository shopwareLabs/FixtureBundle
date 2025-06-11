<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper\CustomField;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldEntity;

class CustomFieldSetFixtureLoader
{
    public function __construct(
        private readonly EntityRepository $customFieldSetRepository,
        private readonly EntityRepository $customFieldRepository
    ) {
    }

    public function load(CustomFieldSetFixtureDefinition $definition): void
    {
        $context = Context::createDefaultContext();

        $customFieldSetId = $this->getOrCreateCustomFieldSet($definition, $context);

        $this->createCustomFields($customFieldSetId, $definition, $context);
    }

    private function getOrCreateCustomFieldSet(CustomFieldSetFixtureDefinition $definition, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $definition->getTechnicalName()));

        $result = $this->customFieldSetRepository->search($criteria, $context);

        if ($result->first()) {
            return $result->first()->getId();
        }

        $customFieldSetId = Uuid::randomHex();

        $labels = $definition->getLabels();
        if (empty($labels)) {
            $labels = [
                'en-GB' => $definition->getName(),
                Defaults::LANGUAGE_SYSTEM => $definition->getName()
            ];
        }

        $data = [
            'id' => $customFieldSetId,
            'name' => $definition->getTechnicalName(),
            'config' => [
                'label' => $labels,
            ],
        ];

        if (!empty($definition->getRelations())) {
            $data['relations'] = array_map(function (string $entityName) {
                return [
                    'id' => Uuid::randomHex(),
                    'entityName' => $entityName,
                ];
            }, $definition->getRelations());
        }

        $this->customFieldSetRepository->create([$data], $context);

        return $customFieldSetId;
    }

    private function createCustomFields(string $customFieldSetId, CustomFieldSetFixtureDefinition $definition, Context $context): void
    {
        $existingFields = $this->getExistingFields($customFieldSetId, $context);
        $fieldsToCreate = [];
        $fieldsToUpdate = [];
        $fieldsToDelete = [];
        $definedFieldNames = [];

        foreach ($definition->getFields() as $name => $fieldData) {
            $technicalName = sprintf('%s_%s', $definition->getTechnicalName(), $name);
            $definedFieldNames[] = $technicalName;

            if (isset($existingFields[$technicalName])) {
                $fieldsToUpdate[] = [
                    'id' => $existingFields[$technicalName]->getId(),
                    'name' => $technicalName,
                    ...$fieldData->build(),
                    'customFieldSetId' => $customFieldSetId,
                ];

                continue;
            }

            $fieldsToCreate[] = [
                'id' => Uuid::randomHex(),
                'name' => $technicalName,
                ... $fieldData->build(),
                'customFieldSetId' => $customFieldSetId,
            ];
        }

        // Delete fields that are not in the definition
        foreach ($existingFields as $fieldName => $field) {
            if (!in_array($fieldName, $definedFieldNames, true)) {
                $fieldsToDelete[] = ['id' => $field->getId()];
            }
        }

        if (!empty($fieldsToDelete)) {
            $this->customFieldRepository->delete($fieldsToDelete, $context);
        }

        if (!empty($fieldsToCreate)) {
            $this->customFieldRepository->create($fieldsToCreate, $context);
        }

        if (!empty($fieldsToUpdate)) {
            $this->customFieldRepository->update($fieldsToUpdate, $context);
        }
    }

    /**
     * @return array<string, CustomFieldEntity>
     */
    private function getExistingFields(string $customFieldSetId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFieldSetId', $customFieldSetId));

        $result = $this->customFieldRepository->search($criteria, $context);

        $fields = [];
        /** @var CustomFieldEntity $field */
        foreach ($result as $field) {
            $fields[$field->getName()] = $field;
        }

        return $fields;
    }
}
