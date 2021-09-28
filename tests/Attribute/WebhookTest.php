<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\AppBundle\Attribute\Webhook;

class WebhookTest extends TestCase
{
    #[Webhook(name: 'name', event: 'event', path: '/my/webhook')]
    public function testWebhookAttribute(): void
    {
        $reflectionClass = new ReflectionClass($this);
        $reflectionMethod = $reflectionClass->getMethod(__FUNCTION__);

        $reflectionAttribute = $reflectionMethod->getAttributes(Webhook::class);

        static::assertEquals($reflectionAttribute[0]->getArguments(), [
            'name' => 'name',
            'event' => 'event',
            'path' => '/my/webhook',
        ]);
    }
}
