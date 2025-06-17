<?php declare(strict_types=1);

use Composer\InstalledVersions;
use Shopware\FixtureBundle\FixtureBundle;
use Shopware\Storefront\Storefront;

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Shopware\Core\Profiling\Profiling::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true, 'test' => true],
    Shopware\Core\Framework\Framework::class => ['all' => true],
    Shopware\Core\System\System::class => ['all' => true],
    Shopware\Core\Content\Content::class => ['all' => true],
    Shopware\Core\Checkout\Checkout::class => ['all' => true],
    Shopware\Core\DevOps\DevOps::class => ['all' => true],
    Shopware\Core\Maintenance\Maintenance::class => ['all' => true],
    Storefront::class => ['all' => true],
    Shopware\Administration\Administration::class => ['all' => true],
    FixtureBundle::class => ['all' => true],
];

return $bundles;