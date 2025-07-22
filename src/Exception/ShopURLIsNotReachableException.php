<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ShopURLIsNotReachableException extends BadRequestHttpException
{
    public function __construct(string $shopUrl, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Shop URL "%s" is not reachable from the application server.',
                $shopUrl
            ),
            $previous
        );
    }
}
