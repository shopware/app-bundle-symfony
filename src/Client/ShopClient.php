<?php declare(strict_types=1);

namespace Shopware\AppBundle\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\AppBundle\Exception\AuthenticationException;
use Shopware\AppBundle\Exception\RegistrationNotCompletedException;
use Shopware\AppBundle\Shop\ShopEntity;

class ShopClient implements ClientInterface
{
    private const AUTHENTICATION_ROUTE = '/api/oauth/token';

    private const AUTH_HEADER = 'Authorization';

    private ?Credentials $credentials;

    public function __construct(
        private ClientInterface $client,
        private ShopEntity $shop
    ) {
        $this->credentials = null;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->credentials === null) {
            $this->credentials = $this->createToken();
        }

        $response = $this->client->sendRequest($request->withHeader(
            self::AUTH_HEADER,
            "{$this->credentials->getTokenType()} {$this->credentials->getAccessToken()}"
        ));

        if ($response->getStatusCode() !== 401) {
            return $response;
        }

        // retry request with updated credentials
        $this->credentials = $this->createToken();

        return $this->client->sendRequest($request->withHeader(
            self::AUTH_HEADER,
            "{$this->credentials->getTokenType()} {$this->credentials->getAccessToken()}"
        ));
    }

    private function createToken(): Credentials
    {
        if (!$this->shop->getApiKey() || !$this->shop->getSecretKey()) {
            throw new RegistrationNotCompletedException($this->shop);
        }

        $authRequest = new Request(
            'POST',
            $this->shop->getUrl() . self::AUTHENTICATION_ROUTE,
            [],
            json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => $this->shop->getApiKey(),
                'client_secret' => $this->shop->getSecretKey(),
            ])
        );

        return $this->requestToken($authRequest);
    }

    private function requestToken(RequestInterface $authRequest): Credentials
    {
        $authenticationResponse = $this->client
            ->sendRequest(
                $authRequest
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Accept', 'application/json')
            );

        if ($authenticationResponse->getStatusCode() >= 400) {
            /** @var string $shopUrl */
            $shopUrl = $this->shop->getUrl();

            /** @var string $apiKey */
            $apiKey = $this->shop->getApiKey();

            throw new AuthenticationException($shopUrl, $apiKey, $authenticationResponse->getBody()->getContents());
        }

        return Credentials::fromAuthResponse($authenticationResponse);
    }
}
