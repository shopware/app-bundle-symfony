<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Shopware\AppBundle\Client\GuzzleClientFactory;
use Shopware\AppBundle\Shop\ShopEntity;

class MockedGuzzleClientFactory extends GuzzleClientFactory
{
    private $mockHandler;

    private $history;

    public function __construct()
    {
        $this->mockHandler = new MockHandler();
        $this->history = [];
    }

    public function getMockHandler(): MockHandler
    {
        return $this->mockHandler;
    }

    public function getHistory(): array
    {
        return $this->history;
    }

    protected function getClientConfiguration(ShopEntity $shop): array
    {
        $configuration = parent::getClientConfiguration($shop);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push(Middleware::history($this->history));

        $configuration['handler'] = $handlerStack;

        return $configuration;
    }
}
