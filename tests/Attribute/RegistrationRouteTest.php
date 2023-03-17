<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\AppBundle\Attribute\RegistrationRoute;

class RegistrationRouteTest extends TestCase
{
    #[RegistrationRoute(name: 'shopware_app.register', path: '/register')]
    public function testRegistrationRouteAttribute(): void
    {
        $reflectionClass = new ReflectionClass($this);
        $reflectionMethod = $reflectionClass->getMethod('testRegistrationRouteAttribute');

        $reflectionAttribute = $reflectionMethod->getAttributes(RegistrationRoute::class);

        static::assertEquals($reflectionAttribute[0]->getArguments(), [
            'path' => '/register',
            'name' => 'shopware_app.register',
        ]);
    }
}
