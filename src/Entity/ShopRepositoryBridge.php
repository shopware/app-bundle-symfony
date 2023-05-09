<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Entity;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Shopware\App\SDK\Exception\ShopNotFoundException;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;

/**
 * @implements ShopRepositoryInterface<AbstractShop>
 */
class ShopRepositoryBridge implements ShopRepositoryInterface
{
    /**
     * @param class-string<AbstractShop> $entityClass
     */
    public function __construct(
        private string $entityClass,
        private ManagerRegistry $registry
    ) {
        if (!is_subclass_of($this->entityClass, AbstractShop::class)) {
            throw new \InvalidArgumentException(sprintf('The shop entity class "%s" must extend "%s"', $this->entityClass, AbstractShop::class));
        }
        if ($this->registry->getManagerForClass($this->entityClass) === null) {
            throw new \InvalidArgumentException(sprintf('The shop entity class "%s" must be a doctrine managed entity', $this->entityClass));
        }
    }

    public function createShopStruct(string $shopId, string $shopUrl, string $shopSecret): ShopInterface
    {
        return new $this->entityClass($shopId, $shopUrl, $shopSecret);
    }

    /**
     * @codeCoverageIgnore
     */
    public function createShop(ShopInterface $shop): void
    {
        $manager = $this->getManager();
        $manager->persist($shop);
        $manager->flush();
    }

    /**
     * @codeCoverageIgnore
     */
    public function getShopFromId(string $shopId): ?ShopInterface
    {
        return $this->registry->getRepository($this->entityClass)->find($shopId);
    }

    /**
     * @codeCoverageIgnore
     */
    public function updateShop(ShopInterface $shop): void
    {
        $this->registry->getManager()->flush();
    }

    /**
     * @codeCoverageIgnore
     */
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

    /**
     * @codeCoverageIgnore
     */
    private function getManager(): ObjectManager
    {
        $manager = $this->registry->getManagerForClass($this->entityClass);
        // we check that $shopEntity is a doctrine entity in the constructor
        assert($manager !== null);
        return $manager;
    }
}
