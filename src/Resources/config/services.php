<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(\Shopware\AppBundle\DependencyInjection\AppConfigurationFactory::class)
        ->args([
            // Will be filled by \Shopware\AppBundle\DependencyInjection\ShopwareAppExtension::load
            '',
            '',
            '',
            service(\Symfony\Component\Routing\Generator\UrlGeneratorInterface::class),
        ]);

    $services->set(\Shopware\App\SDK\HttpClient\ClientFactory::class);

    $services->set(\Shopware\App\SDK\AppConfiguration::class)
        ->factory([service(\Shopware\AppBundle\DependencyInjection\AppConfigurationFactory::class), 'newConfiguration']);

    $services->set(\Shopware\App\SDK\AppLifecycle::class)
        ->args([
            service(\Shopware\App\SDK\Registration\RegistrationService::class),
            service(\Shopware\App\SDK\Shop\ShopResolver::class),
            service(\Shopware\App\SDK\Shop\ShopRepositoryInterface::class),
            service(\Psr\Log\LoggerInterface::class),
            service(\Psr\EventDispatcher\EventDispatcherInterface::class),
        ]);

    $services->set(\Shopware\App\SDK\Authentication\RequestVerifier::class);

    $services->set(\Shopware\App\SDK\Authentication\ResponseSigner::class);

    $services->set(\Symfony\Component\Cache\Psr16Cache::class)
        ->args([service('cache.app')]);

    $services->set(\Shopware\App\SDK\Context\InAppPurchase\SBPStoreKeyFetcher::class)
        ->args([
            service(\Psr\Http\Client\ClientInterface::class),
            service(\Symfony\Component\Cache\Psr16Cache::class),
            service(\Psr\Log\LoggerInterface::class),
        ]);

    $services->set(\Shopware\App\SDK\Context\InAppPurchase\InAppPurchaseProvider::class)
        ->args([
            service(\Shopware\App\SDK\Context\InAppPurchase\SBPStoreKeyFetcher::class),
            service(\Psr\Log\LoggerInterface::class),
        ]);

    $services->set(\Shopware\App\SDK\Context\ContextResolver::class)
        ->args([service(\Shopware\App\SDK\Context\InAppPurchase\InAppPurchaseProvider::class)]);

    $services->set(\Shopware\App\SDK\Shop\ShopResolver::class)
        ->args([service(\Shopware\App\SDK\Shop\ShopRepositoryInterface::class)]);

    $services->set(\Shopware\App\SDK\Registration\RegistrationService::class)
        ->args([
            service(\Shopware\App\SDK\AppConfiguration::class),
            service(\Shopware\App\SDK\Shop\ShopRepositoryInterface::class),
            service(\Shopware\App\SDK\Authentication\RequestVerifier::class),
            service(\Shopware\App\SDK\Authentication\ResponseSigner::class),
            service(\Shopware\App\SDK\Registration\ShopSecretGeneratorInterface::class),
            service(\Psr\Log\LoggerInterface::class),
            service(\Psr\EventDispatcher\EventDispatcherInterface::class),
        ]);

    $services->set(\Shopware\App\SDK\Registration\ShopSecretGeneratorInterface::class, \Shopware\App\SDK\Registration\RandomStringShopSecretGenerator::class);

    $services->set(\Shopware\App\SDK\Shop\ShopRepositoryInterface::class, \Shopware\AppBundle\Entity\ShopRepositoryBridge::class)
        ->args([
            '',
            service(\Doctrine\Persistence\ManagerRegistry::class),
        ]);

    $services->set(\Shopware\AppBundle\Controller\LifecycleController::class)
        ->public()
        ->args([service(\Shopware\App\SDK\AppLifecycle::class)])
        ->tag('controller.service_arguments');

    $services->set(\Shopware\AppBundle\Controller\WebhookController::class)
        ->public()
        ->args([service('event_dispatcher')])
        ->tag('controller.service_arguments');

    $services->set(\Shopware\AppBundle\ArgumentValueResolver\ContextArgumentResolver::class);

    $services->set(\Shopware\AppBundle\EventListener\ResponseSignerListener::class);

    $services->set(\Shopware\AppBundle\EventListener\BeforeRegistrationStartsListener::class)
        ->args([
            service(\Symfony\Contracts\HttpClient\HttpClientInterface::class),
            '%shopware_app.check_if_shop_url_is_reachable%',
        ]);

    $services->set(\Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface::class, \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory::class);

    $services->set(\Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface::class, \Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory::class);

    $services->set(\Symfony\Bridge\PsrHttpMessage\EventListener\PsrResponseListener::class);
};
