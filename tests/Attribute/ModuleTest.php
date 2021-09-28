<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\AppBundle\Attribute\Module;

class ModuleTest extends TestCase
{
    #[Module(path: '/my/module', name: 'my_module', label: ['default' => 'my module', 'de-de' => 'mein Modul'], parent: 'parent', position: 30)]
    public function testModuleAttribute(): void
    {
        $reflectionClass = new ReflectionClass($this);
        $reflectionMethod = $reflectionClass->getMethod(__FUNCTION__);

        $reflectionAttribute = $reflectionMethod->getAttributes(Module::class);

        static::assertEquals($reflectionAttribute[0]->getArguments(), [
            'path' => '/my/module',
            'name' => 'my_module',
            'label' => [
                'default' => 'my module',
                'de-de' => 'mein Modul',
            ],
            'parent' => 'parent',
            'position' => 30,
        ]);
    }
}
