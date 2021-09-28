<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\AppBundle\Attribute\MainModule;

class MainModuleTest extends TestCase
{
    #[MainModule(path: '/my/main/module', name: 'my_main_module')]
    public function testMainModuleAttribute(): void
    {
        $reflectionClass = new ReflectionClass($this);
        $reflectionMethod = $reflectionClass->getMethod(__FUNCTION__);

        $reflectionAttribute = $reflectionMethod->getAttributes(MainModule::class);

        static::assertEquals($reflectionAttribute[0]->getArguments(), [
            'path' => '/my/main/module',
            'name' => 'my_main_module',
        ]);
    }
}
