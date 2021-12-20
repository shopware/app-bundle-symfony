<?php declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

use Shopware\AppBundle\Shop\ShopInterface;

class RegistrationNotCompletedException extends \Exception
{
    public function __construct(ShopInterface $shop)
    {
        parent::__construct("Registration for shop with id {$shop->getId()} and url {$shop->getUrl()} is not completed.");
    }
}
