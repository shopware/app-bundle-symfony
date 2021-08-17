<?php declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

use Shopware\AppBundle\Shop\ShopEntity;

class RegistrationNotCompletedException extends \Exception
{
    public function __construct(ShopEntity $shop)
    {
        parent::__construct("Registration for shop with id {$shop->getId()} and url {$shop->getUrl()} is not completed.");
    }
}
