<?php

declare(strict_types=1);

namespace Shopware\AppBundle\ArgumentValueResolver;

use Psr\Http\Message\RequestInterface;
use Shopware\App\SDK\Context\ActionButton\ActionButtonAction;
use Shopware\App\SDK\Context\ContextResolver;
use Shopware\App\SDK\Context\Gateway\Checkout\CheckoutGatewayAction;
use Shopware\App\SDK\Context\Gateway\InAppFeatures\FilterAction;
use Shopware\App\SDK\Context\Module\ModuleAction;
use Shopware\App\SDK\Context\Payment\PaymentCaptureAction;
use Shopware\App\SDK\Context\Payment\PaymentFinalizeAction;
use Shopware\App\SDK\Context\Payment\PaymentPayAction;
use Shopware\App\SDK\Context\Payment\PaymentValidateAction;
use Shopware\App\SDK\Context\Payment\RefundAction;
use Shopware\App\SDK\Context\Storefront\StorefrontAction;
use Shopware\App\SDK\Context\TaxProvider\TaxProviderAction;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Shopware\AppBundle\AppRequest;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class ContextArgumentResolver implements ValueResolverInterface
{
    private const SUPPORTED_TYPES = [
        RequestInterface::class => true,
        ShopInterface::class => true,
        WebhookAction::class => true,
        ModuleAction::class => true,
        ActionButtonAction::class => true,
        TaxProviderAction::class => true,
        PaymentPayAction::class => true,
        PaymentFinalizeAction::class => true,
        PaymentValidateAction::class => true,
        PaymentCaptureAction::class => true,
        RefundAction::class => true,
        StorefrontAction::class => true,
        CheckoutGatewayAction::class => true,
        FilterAction::class => true,
    ];

    private const SIGNING_REQUIRED_TYPES = [
        ActionButtonAction::class => true,
        TaxProviderAction::class => true,
        PaymentPayAction::class => true,
        PaymentFinalizeAction::class => true,
        PaymentValidateAction::class => true,
        PaymentCaptureAction::class => true,
        RefundAction::class => true,
        CheckoutGatewayAction::class => true,
    ];

    public function __construct(
        private readonly ContextResolver $contextResolver,
        private readonly ShopResolver $shopResolver,
        private readonly HttpMessageFactoryInterface $httpFoundationFactory
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return self::SUPPORTED_TYPES[$argument->getType()] ?? false;
    }

    /**
     * @return iterable<object>
     * @throws \JsonException|\RuntimeException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if(!$this->supports($request, $argument)) {
            return;
        }

        $psrRequest = $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE);

        if (!$psrRequest instanceof RequestInterface) {
            $psrRequest = $this->httpFoundationFactory->createRequest($request);
            $request->attributes->set(AppRequest::PSR_REQUEST_ATTRIBUTE, $psrRequest);
        }

        /** @var class-string $type */
        $type = $argument->getType();

        if ($type === RequestInterface::class) {
            yield $psrRequest;
            return;
        }

        $shop = $request->attributes->get(AppRequest::SHOP_ATTRIBUTE);

        if (!$shop instanceof ShopInterface) {
            $shop = $this->shopResolver->resolveShop($psrRequest);
            $request->attributes->set(AppRequest::SHOP_ATTRIBUTE, $shop);
        }

        if (self::SIGNING_REQUIRED_TYPES[$type] ?? false) {
            $request->attributes->set(AppRequest::SIGN_RESPONSE, true);
        }

        if ($type === ShopInterface::class || in_array(ShopInterface::class, class_implements($type), true)) {
            yield $shop;
            return;
        }

        match ($type) {
            WebhookAction::class => yield $this->contextResolver->assembleWebhook($psrRequest, $shop),
            ModuleAction::class => yield $this->contextResolver->assembleModule($psrRequest, $shop),
            ActionButtonAction::class => yield $this->contextResolver->assembleActionButton($psrRequest, $shop),
            TaxProviderAction::class => yield $this->contextResolver->assembleTaxProvider($psrRequest, $shop),
            PaymentPayAction::class => yield $this->contextResolver->assemblePaymentPay($psrRequest, $shop),
            PaymentFinalizeAction::class => yield $this->contextResolver->assemblePaymentFinalize($psrRequest, $shop),
            PaymentValidateAction::class => yield $this->contextResolver->assemblePaymentValidate($psrRequest, $shop),
            PaymentCaptureAction::class => yield $this->contextResolver->assemblePaymentCapture($psrRequest, $shop),
            RefundAction::class => yield $this->contextResolver->assemblePaymentRefund($psrRequest, $shop),
            StorefrontAction::class => yield $this->contextResolver->assembleStorefrontRequest($psrRequest, $shop),
            CheckoutGatewayAction::class => yield $this->contextResolver->assembleCheckoutGatewayRequest($psrRequest, $shop),
            FilterAction::class => yield $this->contextResolver->assembleInAppPurchasesFilterRequest($psrRequest, $shop),
            default => throw new \RuntimeException(sprintf('Unsupported type %s', $type)),
        };
    }
}
