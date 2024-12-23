<?php

declare(strict_types=1);

namespace Shopware\AppBundle\EventListener;

use Shopware\App\SDK\Event\BeforeRegistrationStartsEvent;
use Shopware\AppBundle\Exception\ShopURLIsNotReachableException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsEventListener]
class BeforeRegistrationStartsListener
{
    private HttpClientInterface $httpClient;

    private bool $checkShopURLIsReachable;

    public function __construct(
        HttpClientInterface $httpClient,
        bool $checkShopURLIsReachable
    ) {
        $this->httpClient = $httpClient;
        $this->checkShopURLIsReachable = $checkShopURLIsReachable;
    }

    public function __invoke(BeforeRegistrationStartsEvent $event): void
    {
        if ($this->checkShopURLIsReachable === false) {
            return;
        }

        $shop = $event->getShop();

        try {
            $this->httpClient->request('HEAD', $shop->getShopUrl(), [
                'timeout' => 10,
                'max_redirects' => 0,
            ]);
        } catch (\Throwable $e) {
            throw new ShopURLIsNotReachableException($shop->getShopUrl(), $e);
        }
    }
}
