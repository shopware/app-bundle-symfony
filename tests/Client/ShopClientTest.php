<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\AppBundle\Client\ShopClient;
use Shopware\AppBundle\Exception\AuthenticationException;
use Shopware\AppBundle\Exception\RegistrationNotCompletedException;
use Shopware\AppBundle\Shop\ShopEntity;

class ShopClientTest extends TestCase
{
    private MockHandler $mockHandler;

    private array $history;

    public function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $this->history = [];
    }

    public function testAuthenticateThrowsExceptionIfShopIsNotRegisteredYet(): void
    {
        $shop = $this->getShop(null, null);

        $client = $this->getClientFromShop($shop);

        static::expectException(RegistrationNotCompletedException::class);
        $client->sendRequest(new Request('POST', new Uri('/some-route')));
    }

    public function testItAuthenticatesAndSendsAnRequest(): void
    {
        $shop = $this->getShop();

        $client = $this->getClientFromShop($shop);

        $this->mockHandler->append(
            $this->getAuthResponse(),
            new Response(204, [], null)
        );

        $client->sendRequest(new Request('POST', new Uri('/some-route')));

        static::assertCount(2, $this->history);

        /** @var RequestInterface $authenticationRequest */
        $authenticationRequest = $this->history[0]['request'];

        static::assertEquals('POST', $authenticationRequest->getMethod());
        static::assertEquals('/api/oauth/token', $authenticationRequest->getRequestTarget());
        static::assertEquals([
            'grant_type' => 'client_credentials',
            'client_id' => $shop->getApiKey(),
            'client_secret' => $shop->getSecretKey(),
        ], json_decode($authenticationRequest->getBody()->getContents(), true));

        $lastRequest = $this->history[1]['request'];

        static::assertEquals('/some-route', $lastRequest->getRequestTarget());
        static::assertEquals([
            'Bearer shopware-access-token',
        ], $lastRequest->getHeader('Authentication'));
    }

    public function testItRefreshesTheTokenIfTheClientReturnsAn401(): void
    {
        $shop = $this->getShop();

        $client = $this->getClientFromShop($shop);

        $this->mockHandler->append(
            $this->getAuthResponse(),
            new Response(401, [], null),
            $this->getRefreshAuthResponse(),
            new Response(200, [], null)
        );

        $client->sendRequest(new Request('POST', new Uri('/some-route')));

        $lastRequest = $this->mockHandler->getLastRequest();

        static::assertEquals('/some-route', $lastRequest->getRequestTarget());
        static::assertEquals([
            'Bearer refreshed-shopware-access-token',
        ], $lastRequest->getHeader('Authentication'));
    }

    public function testItThrowsAuthenticationExceptionIfAuthenticationCallFails(): void
    {
        $shop = $this->getShop();

        $client = $this->getClientFromShop($shop);

        $this->mockHandler->append(
            new Response(403, [], null),
        );

        static::expectException(AuthenticationException::class);
        $client->sendRequest(new Request('POST', new Uri('/some-route')));
    }

    private function getShop(?string $apiKey = 'integration-key', ?string $secretKey = 'integration-secret'): ShopEntity
    {
        return new ShopEntity(
            'a-unique-id',
            'https://test.shop',
            'i-am-secret',
            $apiKey,
            $secretKey
        );
    }

    private function getClientFromShop(ShopEntity $shop): ShopClient
    {
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push(Middleware::history($this->history));

        return new ShopClient(
            new Client(
                [
                    'base_url' => $shop->getUrl(),
                    'handler' => $handlerStack,
                ]
            ),
            $shop
        );
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
                'refresh_token' => 'shopware-refresh-token',
            ])
        );
    }

    private function getRefreshAuthResponse(): ResponseInterface
    {
        return new Response(
            200,
            ['content-type' => 'application/json'],
            json_encode([
                'token_type' => 'Bearer',
                'expires_in' => 600,
                'access_token' => 'refreshed-shopware-access-token',
                'refresh_token' => 'shopware-refresh-token',
            ])
        );
    }
}
