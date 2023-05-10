<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Controller;

use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsController]
final class WebhookController
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function dispatch(WebhookAction $webhookAction): Response
    {
        $this->eventDispatcher->dispatch($webhookAction, 'webhook.' . $webhookAction->eventName);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
