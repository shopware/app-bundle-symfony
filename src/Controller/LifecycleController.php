<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Controller;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\App\SDK\AppLifecycle;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class LifecycleController
{
    public function __construct(
        private readonly AppLifecycle $appLifecycle
    ) {
    }

    public function register(RequestInterface $request): ResponseInterface
    {
        return $this->appLifecycle->register($request);
    }

    public function registerConfirm(RequestInterface $request): ResponseInterface
    {
        return $this->appLifecycle->registerConfirm($request);
    }

    public function activate(RequestInterface $request): ResponseInterface
    {
        return $this->appLifecycle->activate($request);
    }

    public function deactivate(RequestInterface $request): ResponseInterface
    {
        return $this->appLifecycle->deactivate($request);
    }

    public function delete(RequestInterface $request): ResponseInterface
    {
        return $this->appLifecycle->delete($request);
    }
}
