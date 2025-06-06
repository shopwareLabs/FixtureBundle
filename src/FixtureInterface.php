<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle;

interface FixtureInterface
{
    public function load(): void;
}