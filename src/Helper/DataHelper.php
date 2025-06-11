<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleCollection;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\Tax\TaxCollection;
use Shopware\Core\System\Tax\TaxEntity;

class DataHelper
{
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry
    ) {
    }

    public function getSalesChannel(
        ?string $typeId = Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
        ?string $name = null
    ): ?SalesChannelEntity {
        /** @var EntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = $this->definitionRegistry->getRepository('sales_channel');

        $criteria = new Criteria();

        if ($typeId !== null) {
            $criteria->addFilter(new EqualsFilter('typeId', $typeId));
        }

        if ($name !== null) {
            $criteria->addFilter(new EqualsFilter('name', $name));
        }

        $criteria->setLimit(1);

        $result = $salesChannelRepository->search($criteria, Context::createCLIContext());

        return $result->first();
    }

    public function getSalutation(string $salutationKey = 'not_specified'): ?SalutationEntity
    {
        /** @var EntityRepository<SalutationCollection> $salutationRepository */
        $salutationRepository = $this->definitionRegistry->getRepository('salutation');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));
        $criteria->setLimit(1);

        $result = $salutationRepository->search($criteria, Context::createCLIContext());

        return $result->first();
    }

    public function getCountry(?string $iso3 = null): ?CountryEntity
    {
        /** @var EntityRepository<CountryCollection> $countryRepository */
        $countryRepository = $this->definitionRegistry->getRepository('country');

        $criteria = new Criteria();

        if ($iso3 !== null) {
            $criteria->addFilter(new EqualsFilter('iso3', $iso3));
        }

        $criteria->setLimit(1);

        $result = $countryRepository->search($criteria, Context::createCLIContext());

        return $result->first();
    }

    public function getLocale(string $code): ?LocaleEntity
    {
        /** @var EntityRepository<LocaleCollection> $localeRepository */
        $localeRepository = $this->definitionRegistry->getRepository('locale');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $code));
        $criteria->setLimit(1);

        $result = $localeRepository->search($criteria, Context::createCLIContext());

        return $result->first();
    }

    public function getLanguage(string $name): ?LanguageEntity
    {
        /** @var EntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = $this->definitionRegistry->getRepository('language');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $criteria->setLimit(1);

        $result = $languageRepository->search($criteria, Context::createCLIContext());

        return $result->first();
    }

    public function getCurrency(string $isoCode = 'EUR'): ?CurrencyEntity
    {
        /** @var EntityRepository<CurrencyCollection> $currencyRepository */
        $currencyRepository = $this->definitionRegistry->getRepository('currency');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $isoCode));
        $criteria->setLimit(1);

        $result = $currencyRepository->search($criteria, Context::createCLIContext());

        return $result->first();
    }

    public function getTax(float $taxRate): ?TaxEntity
    {
        /** @var EntityRepository<TaxCollection> $taxRepository */
        $taxRepository = $this->definitionRegistry->getRepository('tax');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));
        $criteria->setLimit(1);

        $result = $taxRepository->search($criteria, Context::createCLIContext());

        return $result->first();
    }
}
