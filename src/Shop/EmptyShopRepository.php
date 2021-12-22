<?php declare(strict_types=1);

namespace Shopware\AppBundle\Shop;

use Shopware\AppBundle\Exception\DecorationRequiredException;

/**
 * @codeCoverageIgnore
 */
class EmptyShopRepository implements ShopRepositoryInterface
{
    public function createShop(ShopInterface $shop): void
    {
        $this->throwException();
    }

    public function getShopFromId(string $shopId): ShopInterface
    {
        $this->throwException();
    }

    public function updateShop(ShopInterface $shop): void
    {
        $this->throwException();
    }

    public function deleteShop(ShopInterface $shop): void
    {
        $this->throwException();
    }

    private function throwException(): void
    {
        throw new DecorationRequiredException(ShopRepositoryInterface::class);
    }
}
