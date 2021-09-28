<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\AppBundle\Attribute\ActionButton;

class ActionButtonTest extends TestCase
{
    #[ActionButton(action: 'action', entity: 'entity', view: 'view', label: ['default' => 'default', 'en-GB' => 'translation'], path: '/my/action/button')]
    public function testActionButtonAttribute(): void
    {
        $reflectionClass = new ReflectionClass($this);
        $reflectionMethod = $reflectionClass->getMethod(__FUNCTION__);

        $reflectionAttribute = $reflectionMethod->getAttributes(ActionButton::class);

        static::assertEquals($reflectionAttribute[0]->getArguments(), [
            'action' => 'action',
            'entity' => 'entity',
            'view' => 'view',
            'label' => [
                'default' => 'default',
                'en-GB' => 'translation',
            ],
            'path' => '/my/action/button',
        ]);
    }
}
