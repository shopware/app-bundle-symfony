<?php

declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use Psr\Http\Message\RequestInterface;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Shopware\AppBundle\AppRequest;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ShopProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ShopResolver $shopResolver,
        private readonly HttpMessageFactoryInterface $httpFoundationFactory
    ) {
    }

    public function provide(): ?ShopInterface
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if (!$currentRequest instanceof Request) {
            return null;
        }

        $psrRequest = $currentRequest->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE);

        if (!$psrRequest instanceof RequestInterface) {
            $psrRequest = $this->httpFoundationFactory->createRequest($currentRequest);
            $currentRequest->attributes->set(AppRequest::PSR_REQUEST_ATTRIBUTE, $psrRequest);
        }

        $shop = $currentRequest->attributes->get(AppRequest::SHOP_ATTRIBUTE);

        if (!$shop instanceof ShopInterface) {
            $shop = $this->shopResolver->resolveShop($psrRequest);
            $currentRequest->attributes->set(AppRequest::SHOP_ATTRIBUTE, $shop);
        }

        return $shop;
    }
}
