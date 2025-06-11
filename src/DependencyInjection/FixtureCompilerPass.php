<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\DependencyInjection;

use Shopware\FixtureBundle\FixtureCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FixtureCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $collection = $container->getDefinition(FixtureCollection::class);

        foreach ($container->findTaggedServiceIds('shopware.fixture') as $id => $tags) {
            $collection->addMethodCall('add', [
                new Reference($id),
                $tags[0]['priority'] ?? 0,
                $tags[0]['dependsOn'] ?? [],
                $tags[0]['groups'] ?? ['default'],
            ]);
        }
    }
}
