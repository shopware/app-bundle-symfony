<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Controller;

use Psr\Http\Message\RequestInterface;
use Shopware\App\SDK\Registration\RegistrationService;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

class LifecycleController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function __construct(
        private readonly RegistrationService   $registrationService,
        private readonly HttpFoundationFactoryInterface $symfonyHttpFactory
    ) {
    }

    public function register(RequestInterface $request): Response
    {
        $proof = $this->registrationService->register($request);

        return $this->symfonyHttpFactory->createResponse($proof);
    }

    public function confirm(RequestInterface $request): Response
    {
        $proof = $this->registrationService->registerConfirm($request);

        return $this->symfonyHttpFactory->createResponse($proof);
    }
}
