<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Fixture
{
    /**
     * @param int $priority Higher priority fixtures are loaded first
     * @param string[] $dependsOn Array of fixture class names this fixture depends on
     * @param string[] $groups Array of group names this fixture belongs to
     */
    public function __construct(
        public readonly int $priority = 0,
        public readonly array $dependsOn = [],
        public readonly array $groups = ['default']
    ) {
    }
}
