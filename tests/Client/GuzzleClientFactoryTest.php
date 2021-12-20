<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Shopware\AppBundle\Client\GuzzleClientFactory;
use Shopware\AppBundle\Client\ShopClient;
use Shopware\AppBundle\Shop\ShopEntity;

class GuzzleClientFactoryTest extends TestCase
{
    public function testItReturnsAShopClient(): void
    {
        $clientFactory = new GuzzleClientFactory();

        static::assertInstanceOf(
            ShopClient::class,
            $clientFactory->createClient(
                new ShopEntity('shopId', 'shopUrl', 'secret')
            )
        );
    }

    public function testClientRespectsSubDomains(): void
    {
        $clientFactory = new MockedGuzzleClientFactory();

        $client = $clientFactory->createClient(new ShopEntity(
            'shopId',
            'https://shop.domain/sub/domain',
            'secret',
            'abcd',
            'efgh',
        ));

        $clientFactory->getMockHandler()->append(
            $this->getAuthResponse(),
            new Response(200, [], '[]'),
        );

        $client->sendRequest(new Request('POST', 'api/search/product', [], '{}'));

        $request = $clientFactory->getMockHandler()->getLastRequest();

        static::assertEquals('/sub/domain/api/search/product', $request->getRequestTarget());
    }

    private function getAuthResponse(): ResponseInterface
    {
        return new Response(
            200,
            ['content-type' => 'application/json'],
            json_encode([
                'token_type' => 'Bearer',
                'expires_in' => 600,
                'access_token' => 'shopware-access-token',
            ])
        );
    }
}
