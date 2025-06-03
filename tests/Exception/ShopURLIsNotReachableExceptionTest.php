<?php

declare(strict_types=1);

namespace Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\Exception\ShopURLIsNotReachableException;

#[CoversClass(ShopURLIsNotReachableException::class)]
class ShopURLIsNotReachableExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $shopUrl = 'http://example.com';

        $exception = new ShopURLIsNotReachableException($shopUrl);

        static::assertSame(
            sprintf(
                'Shop URL "%s" is not reachable from the application server.',
                $shopUrl
            ),
            $exception->getMessage()
        );

        static::assertSame(0, $exception->getCode());
    }
}
