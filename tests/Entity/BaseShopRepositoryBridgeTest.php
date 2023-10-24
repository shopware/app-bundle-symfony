<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Shopware\App\SDK\Exception\ShopNotFoundException;
use Shopware\AppBundle\Entity\AbstractShop;
use Shopware\AppBundle\Entity\ShopRepositoryBridge;
use PHPUnit\Framework\TestCase;

class BaseShopRepositoryBridgeTest extends TestCase
{
    public function testConstructionFailsWithIncorrectShopEntity(): void
    {
        static::expectException(\InvalidArgumentException::class);

        $brokenEntity = new class () {
        };

        $managerRegistry = static::createMock(ManagerRegistry::class);
        $managerRegistry
            ->method('getManagerForClass')
            ->with($brokenEntity::class)
            ->willReturn(static::createMock(EntityManagerInterface::class));

        new ShopRepositoryBridge(
            $brokenEntity::class,
            $managerRegistry
        );
    }


    public function testConstructionFailsForNonDoctrineEntities(): void
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
            static::createMock(ManagerRegistry::class)
        );
    }

    public function testBridgeCanConstructCustomShopEntity(): void
    {
        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };

        $managerRegistry = static::createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')
            ->willReturn(
                static::createMock(EntityManagerInterface::class)
            );

        $bridge = new ShopRepositoryBridge(
            $customShop::class,
            $managerRegistry
        );

        static::assertInstanceOf($customShop::class, $bridge->createShopStruct('id', 'url', 'secret'));
    }

    public function testCreatShopStruct(): void
    {
        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };

        $registry = static::createMock(ManagerRegistry::class);
        $registry
            ->expects(static::once())
            ->method('getManagerForClass')
            ->with($customShop::class)
            ->willReturn(static::createMock(EntityManagerInterface::class));

        $bridge = new ShopRepositoryBridge($customShop::class, $registry);

        $shop = $bridge->createShopStruct('id', 'url', 'secret');

        static::assertInstanceOf($customShop::class, $shop);
        static::assertSame('id', $shop->getShopId());
        static::assertSame('url', $shop->getShopUrl());
        static::assertSame('secret', $shop->getShopSecret());
    }

    public function testCreateShop(): void
    {
        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };

        $manager = static::createMock(EntityManagerInterface::class);
        $manager
            ->expects(static::once())
            ->method('persist')
            ->with($customShop);

        $manager
            ->expects(static::once())
            ->method('flush');

        $registry = static::createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with($customShop::class)
            ->willReturn($manager);

        $bridge = new ShopRepositoryBridge($customShop::class, $registry);
        $bridge->createShop($customShop);
    }

    public function testUpdateShop(): void
    {
        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };

        $manager = static::createMock(EntityManagerInterface::class);
        $manager
            ->expects(static::once())
            ->method('flush');

        $registry = static::createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with($customShop::class)
            ->willReturn($manager);

        $registry
            ->method('getManager')
            ->willReturn($manager);

        $bridge = new ShopRepositoryBridge($customShop::class, $registry);
        $bridge->updateShop($customShop);
    }

    public function testDeleteShop(): void
    {
        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };

        $manager = static::createMock(EntityManagerInterface::class);
        $manager
            ->expects(static::once())
            ->method('find')
            ->with($customShop::class, $customShop->getShopId())
            ->willReturn($customShop);

        $manager
            ->expects(static::once())
            ->method('remove')
            ->with($customShop);

        $manager
            ->expects(static::once())
            ->method('flush');

        $registry = static::createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with($customShop::class)
            ->willReturn($manager);

        $registry
            ->method('getManager')
            ->willReturn($manager);

        $bridge = new ShopRepositoryBridge($customShop::class, $registry);
        $bridge->deleteShop($customShop->getShopId());
    }

    public function testDeleteShopWithoutShop(): void
    {
        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };

        $manager = static::createMock(EntityManagerInterface::class);
        $manager
            ->expects(static::once())
            ->method('find')
            ->with($customShop::class, $customShop->getShopId())
            ->willReturn(null);

        $registry = static::createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with($customShop::class)
            ->willReturn($manager);

        $registry
            ->method('getManager')
            ->willReturn($manager);

        $bridge = new ShopRepositoryBridge($customShop::class, $registry);

        static::expectException(ShopNotFoundException::class);

        $bridge->deleteShop($customShop->getShopId());
    }

    public function testGetShopFromId(): void
    {
        $customShop = new class ('', '', '') extends AbstractShop {
            public function __construct(string $shopId, string $shopUrl, string $shopSecret)
            {
                parent::__construct($shopId, $shopUrl, $shopSecret);
            }
        };

        $repository = static::createMock(EntityRepository::class);
        $repository
            ->expects(static::once())
            ->method('find')
            ->with($customShop->getShopId())
            ->willReturn($customShop);

        $registry = static::createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with($customShop::class)
            ->willReturn(static::createMock(EntityManagerInterface::class));

        $registry
            ->method('getRepository')
            ->with($customShop::class)
            ->willReturn($repository);

        $bridge = new ShopRepositoryBridge($customShop::class, $registry);
        $bridge->getShopFromId($customShop->getShopId());
    }
}
