<?php declare(strict_types=1);

namespace Shopware\AppBundle\Shop;

use Shopware\AppBundle\Exception\DecorationRequiredException;

/**
 * @codeCoverageIgnore
 */
class EmptyShopRepository implements ShopRepositoryInterface
{
    public function createShop(ShopEntity $shop): void
    {
        $this->throwException();
    }

    public function getShopFromId(string $shopId): ShopEntity
    {
        $this->throwException();
    }

    public function updateShop(ShopEntity $shop): void
    {
        $this->throwException();
    }

    public function deleteShop(ShopEntity $shop): void
    {
        $this->throwException();
    }

    private function throwException(): void
    {
        throw new DecorationRequiredException(ShopRepositoryInterface::class);
    }
}
