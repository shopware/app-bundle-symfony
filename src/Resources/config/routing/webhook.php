<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Shopware\AppBundle\Controller\WebhookController;

return function (RoutingConfigurator $routes): void {
    $routes->add('shopware_app_webhook', '/webhook')
        ->methods([Request::METHOD_POST])
        ->controller([WebhookController::class, 'dispatch']);
};
