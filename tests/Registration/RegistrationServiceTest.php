<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Registration;

use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Shopware\AppBundle\Authentication\RequestVerifier;
use Shopware\AppBundle\Authentication\ResponseSigner;
use Shopware\AppBundle\Metadata;
use Shopware\AppBundle\Registration\RegistrationService;
use Shopware\AppBundle\Registration\ShopSecretGeneratorInterface;
use Shopware\AppBundle\Shop\ShopEntity;
use Shopware\AppBundle\Shop\ShopRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RegistrationServiceTest extends KernelTestCase
{
    private RegistrationService $registrationService;

    private ShopRepositoryInterface $shopRepositoryMock;

    private ShopSecretGeneratorInterface $shopSecretGenerator;

    private Metadata $metadata;

    public function setUp(): void
    {
        $this->metadata = $this->getContainer()->get(Metadata::class);

        $this->shopRepositoryMock = $this->createMock(ShopRepositoryInterface::class);

        $this->shopSecretGenerator = $this->createMock(ShopSecretGeneratorInterface::class);

        $this->registrationService = new RegistrationService(
            $this->getContainer()->getParameter('shopware_app.setup.secret'),
            $this->shopRepositoryMock,
            new RequestVerifier(),
            new ResponseSigner($this->metadata),
            $this->shopSecretGenerator
        );
    }

    public function testHandleShopRegistrationRequest(): void
    {
        $shopId = 'unique-id';
        $shopUrl = 'shop.test';
        $shopSecret = $this->getContainer()->getParameter('shopware_app.setup.secret');

        $query = Query::build([
            'shop-id' => $shopId,
            'shop-url' => $shopUrl,
            'timestamp' => 'now',
        ]);

        $signature = hash_hmac('sha256', $query, $shopSecret);

        $registrationRequest = new Request(
            'GET',
            (new Uri(''))->withQuery($query),
            [
                'shopware-app-signature' => $signature,
            ]
        );

        $this->shopSecretGenerator
            ->expects(static::once())
            ->method('generate')
            ->willReturn($shopSecret);

        $this->shopRepositoryMock
            ->expects(static::once())
            ->method('createShop')
            ->willReturnCallback(function (ShopEntity $shop) use ($shopId, $shopUrl, $shopSecret): void {
                static::assertEquals($shopId, $shop->getId());
                static::assertEquals($shopUrl, $shop->getUrl());
                static::assertEquals($shopSecret, $shop->getShopSecret());
            });

        $registration = $this->registrationService->handleShopRegistrationRequest($registrationRequest, '/confirm');

        $expectedProof = hash_hmac(
            'sha256',
            $shopId . $shopUrl . $this->metadata->getName(),
            $shopSecret
        );

        static::assertEquals([
            'proof' => $expectedProof,
            'confirmation_url' => '/confirm',
            'secret' => $shopSecret,
        ], $registration);
    }

    public function testHandleConfirmationRequest(): void
    {
        $shopId = 'unique-id';
        $shopUrl = 'test.shop';
        $shopSecret = 'this-is-secret';

        $body = json_encode([
            'shopId' => $shopId,
            'apiKey' => 'api',
            'secretKey' => 'secret',
        ]);

        $signature = hash_hmac('sha256', $body, $shopSecret);

        $request = new Request(
            'POST',
            '/',
            [
                'shopware-shop-signature' => $signature,
            ],
            $body
        );

        $this->shopRepositoryMock
            ->expects(static::once())
            ->method('getShopFromId')
            ->with($shopId)
            ->willReturn(new ShopEntity($shopId, $shopUrl, $shopSecret));

        $this->shopRepositoryMock
            ->expects(static::once())
            ->method('updateShop')
            ->willReturnCallback(function (ShopEntity $shop) use ($shopId, $shopUrl, $shopSecret): void {
                static::assertEquals($shopId, $shop->getId());
                static::assertEquals($shopUrl, $shop->getUrl());
                static::assertEquals($shopSecret, $shop->getShopSecret());
                static::assertEquals('api', $shop->getApiKey());
                static::assertEquals('secret', $shop->getSecretKey());
            });

        $this->registrationService->handleConfirmation($request);
    }
}
