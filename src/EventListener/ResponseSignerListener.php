<?php

declare(strict_types=1);

namespace Shopware\AppBundle\EventListener;

use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\AppBundle\AppRequest;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * This listener runs after the PSR converter of Symfony, so we can sign the response
 */
#[AsEventListener]
class ResponseSignerListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$event->getRequest()->attributes->has(AppRequest::SIGN_RESPONSE)) {
            return;
        }

        /** @var ShopInterface $shop */
        $shop = $event->getRequest()->attributes->get(AppRequest::SHOP_ATTRIBUTE);

        $content = $response->getContent();

        if ($content === '' || $content === false) {
            return;
        }

        $response->headers->set('shopware-app-signature', hash_hmac('sha256', $content, $shop->getShopSecret()));
    }
}
