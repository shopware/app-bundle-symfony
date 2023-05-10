<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\App\SDK\Context\ActionSource;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\AppBundle\Controller\WebhookController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WebhookControllerTest extends TestCase
{
    public function testDispatchesWebhookAction(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $controller = new WebhookController(
            $dispatcher
        );

        $webhook = new WebhookAction(
            $this->createMock(ShopInterface::class),
            $this->createMock(ActionSource::class),
            'product.written',
            [],
            new \DateTimeImmutable()
        );

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($webhook, 'webhook.product.written');

        $response = $controller->dispatch($webhook);
        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
