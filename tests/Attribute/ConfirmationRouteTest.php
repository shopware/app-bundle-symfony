<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\AppBundle\Attribute\ConfirmationRoute;

class ConfirmationRouteTest extends TestCase
{
    #[ConfirmationRoute('/confirm', name: 'shopware_app.confirm', methods: ['POST'])]
    public function testRegistrationRouteAttribute(): void
    {
        $reflectionClass = new ReflectionClass($this);
        $reflectionMethod = $reflectionClass->getMethod('testRegistrationRouteAttribute');

        $reflectionAttribute = $reflectionMethod->getAttributes(ConfirmationRoute::class);

        static::assertEquals($reflectionAttribute[0]->getArguments(), [
            '0' => '/confirm',
            'name' => 'shopware_app.confirm',
            'methods' => [
                'POST',
            ],
        ]);
    }
}
