<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\Controller;

use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Shopware\App\SDK\AppLifecycle;
use Shopware\AppBundle\Controller\LifecycleController;

class LifecycleControllerTest extends TestCase
{
    public function testRegister(): void
    {
        $request = new Request('GET', '/lifecycle');

        $lifecycle = $this->createMock(AppLifecycle::class);
        $lifecycle
            ->expects(static::once())
            ->method('register')
            ->with($request);
        $controller = new LifecycleController($lifecycle);
        $controller->register($request);
    }

    public function testRegisterConfirm(): void
    {
        $request = new Request('GET', '/lifecycle');

        $lifecycle = $this->createMock(AppLifecycle::class);
        $lifecycle
            ->expects(static::once())
            ->method('registerConfirm')
            ->with($request);
        $controller = new LifecycleController($lifecycle);
        $controller->registerConfirm($request);
    }

    public function testActivate(): void
    {
        $request = new Request('GET', '/lifecycle');

        $lifecycle = $this->createMock(AppLifecycle::class);
        $lifecycle
            ->expects(static::once())
            ->method('activate')
            ->with($request);
        $controller = new LifecycleController($lifecycle);
        $controller->activate($request);
    }

    public function testDeactivate(): void
    {
        $request = new Request('GET', '/lifecycle');

        $lifecycle = $this->createMock(AppLifecycle::class);
        $lifecycle
            ->expects(static::once())
            ->method('deactivate')
            ->with($request);
        $controller = new LifecycleController($lifecycle);
        $controller->deactivate($request);
    }

    public function testDelete(): void
    {
        $request = new Request('GET', '/lifecycle');

        $lifecycle = $this->createMock(AppLifecycle::class);
        $lifecycle
            ->expects(static::once())
            ->method('delete')
            ->with($request);
        $controller = new LifecycleController($lifecycle);
        $controller->delete($request);
    }
}
