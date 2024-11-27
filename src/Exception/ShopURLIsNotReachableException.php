<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

class ShopURLIsNotReachableException extends \RuntimeException
{
    public function __construct(string $shopUrl, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Shop URL "%s" is not reachable from the internet and cannot be registered.',
                $shopUrl
            ),
            0,
            $previous
        );
    }
}
