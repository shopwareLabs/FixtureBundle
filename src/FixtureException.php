<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class FixtureException extends ShopwareHttpException
{
    public const THEME_NOT_FOUND = 'FIXTURE__THEME_NOT_FOUND';

    public static function themeNotFound(string $themeName): self
    {
        return new self(
            'Theme "{{ themeName }}" not found.',
            ['themeName' => $themeName]
        );
    }

    public function getErrorCode(): string
    {
        return self::THEME_NOT_FOUND;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
