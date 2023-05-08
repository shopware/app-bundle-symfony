<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Entity;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Shopware\App\SDK\Exception\ShopNotFoundException;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;

/**
 * @template TShop of Shop
 */
class ShopRepositoryBridge implements ShopRepositoryInterface
{
    /**
     * @param class-string<TShop> $entityClass
     */
    public function __construct(
        private string $entityClass,
        private ManagerRegistry $registry
    ) {
        if (!is_subclass_of($this->entityClass, Shop::class)) {
            throw new \InvalidArgumentException(sprintf('The shop entity class "%s" must extend "%s"', $this->entityClass, Shop::class));
        }
        if ($this->registry->getManagerForClass($this->entityClass) === null) {
            throw new \InvalidArgumentException(sprintf('The shop entity class "%s" must be a doctrine managed entity', $this->entityClass));
        }
    }

    /**
     * @return TShop
     */
    public function createShopStruct(string $shopId, string $shopUrl, string $shopSecret): ShopInterface
    {
        return new $this->entityClass($shopId, $shopUrl, $shopSecret);
    }

    public function createShop(ShopInterface $shop): void
    {
        $manager = $this->getManager();
        $manager->persist($shop);
        $manager->flush();
    }

    /**
     * @return ?TShop
     */
    public function getShopFromId(string $shopId): ?ShopInterface
    {
        return $this->registry->getRepository($this->entityClass)->find($shopId);
    }

    public function updateShop(ShopInterface $shop): void
    {
        $entity = $this->registry->getRepository($this->entityClass)->find($shop->getShopId());
        if (!$entity) {
            throw new ShopNotFoundException($shop->getShopId());
        }
        $entity->setShopUrl($shop->getShopUrl());
        $entity->setShopSecret($shop->getShopSecret());
        $entity->setShopClientId($shop->getShopClientId());
        $entity->setShopClientSecret($shop->getShopClientSecret());
        $this->registry->getManager()->flush();
    }

    public function deleteShop(string $shopId): void
    {
        $manager = $this->getManager();
        $entity = $manager->find($this->entityClass, $shopId);
        if (!$entity) {
            throw new ShopNotFoundException($shopId);
        }
        $manager->remove($entity);
        $manager->flush();
    }

    public function getManager(): ObjectManager
    {
        $manager = $this->registry->getManagerForClass($this->entityClass);
        // we check that $shopEntity is a doctrine entity in the constructor
        assert($manager !== null);
        return $manager;
    }


}
