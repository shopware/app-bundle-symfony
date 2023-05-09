<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Shopware\AppBundle\Entity\AbstractShop;
use Shopware\AppBundle\Entity\ShopRepositoryBridge;
use PHPUnit\Framework\TestCase;

class BaseShopRepositoryBridgeTest extends TestCase
{
    public function testConstructionFailsWithIncorrectShopEntity()
    {
        $this->expectException(\InvalidArgumentException::class);

        $brokenEntity = new class () {
        };

        new ShopRepositoryBridge(
            $brokenEntity::class,
            $this->createMock(ManagerRegistry::class)
        );
    }


    public function testConstructionFailsForNonDoctrineEntities()
    {
        $this->expectException(\InvalidArgumentException::class);


        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };


        new ShopRepositoryBridge(
            $customShop::class,
            $this->createMock(ManagerRegistry::class)
        );
    }

    public function testBridgeCanConstructCustomShopEntity()
    {
        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')
            ->willReturn(
                $this->createMock(EntityManagerInterface::class)
            );

        $bridge = new ShopRepositoryBridge(
            $customShop::class,
            $managerRegistry
        );

        $this->assertInstanceOf($customShop::class, $bridge->createShopStruct('id', 'url', 'secret'));
    }
}
