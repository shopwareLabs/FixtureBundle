<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle;

use Shopware\Core\Framework\Bundle;
use Shopware\FixtureBundle\Attribute\Fixture;
use Shopware\FixtureBundle\DependencyInjection\FixtureCompilerPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FixtureBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerAttributeForAutoconfiguration(Fixture::class, function (ChildDefinition $definition, Fixture $fixture): void {
            $definition->addTag('shopware.fixture', [
                'priority' => $fixture->priority,
                'dependsOn' => $fixture->dependsOn,
                'groups' => $fixture->groups,
            ]);
        });

        $container->addCompilerPass(new FixtureCompilerPass());
    }
}
