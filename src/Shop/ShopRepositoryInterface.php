<?php declare(strict_types=1);

namespace Shopware\AppBundle\Shop;

interface ShopRepositoryInterface
{
    /**
     * @deprecated tag:v1.0.0 - Will only accept ShopInterface as paramater
     */
    public function createShop(ShopInterface|ShopEntity $shop): void;

    /**
     * @deprecated tag:v1.0.0 - Will only accept ShopInterface as paramater
     */
    public function getShopFromId(string $shopId): ShopInterface|ShopEntity;

    /**
     * @deprecated tag:v1.0.0 - Will only accept ShopInterface as paramater
     */
    public function updateShop(ShopInterface|ShopEntity $shop): void;

    /**
     * @deprecated tag:v1.0.0 - Will only accept ShopInterface as paramater
     */
    public function deleteShop(ShopInterface|ShopEntity $shop): void;
}
