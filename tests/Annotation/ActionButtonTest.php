<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Annotation;

use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\Annotation\ActionButton;

class ActionButtonTest extends TestCase
{
    #[ActionButton(action: 'action', entity: 'entity', view: 'view', label: ['default' => 'default', 'en-GB' => 'translation'])]
    public function testActionButtonAnnotation(): void
    {
        $reflectionClass = new \ReflectionClass($this);
        $reflectionMethod = $reflectionClass->getMethod('testActionButtonAnnotation');

        $reflectionAttribute = $reflectionMethod->getAttributes(ActionButton::class);

        static::assertEquals($reflectionAttribute[0]->getArguments(), [
            'action' => 'action',
            'entity' => 'entity',
            'view' => 'view',
            'label' => [
                'default' => 'default',
                'en-GB' => 'translation',
            ],
        ]);
    }
}
