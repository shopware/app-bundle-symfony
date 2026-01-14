<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Shopware\AppBundle\Controller\LifecycleController;

return function (RoutingConfigurator $routes): void {
    $routes->add('shopware_app_lifecycle_register', '/lifecycle/register')
        ->methods([Request::METHOD_GET])
        ->controller([LifecycleController::class, 'register']);

    $routes->add('shopware_app_lifecycle_confirm', '/lifecycle/register-confirm')
        ->methods([Request::METHOD_POST])
        ->controller([LifecycleController::class, 'registerConfirm']);

    $routes->add('shopware_app_lifecycle_activate', '/lifecycle/activate')
        ->methods([Request::METHOD_POST])
        ->controller([LifecycleController::class, 'activate']);

    $routes->add('shopware_app_lifecycle_deactivate', '/lifecycle/deactivate')
        ->methods([Request::METHOD_POST])
        ->controller([LifecycleController::class, 'deactivate']);

    $routes->add('shopware_app_lifecycle_delete', '/lifecycle/delete')
        ->methods([Request::METHOD_POST])
        ->controller([LifecycleController::class, 'delete']);
};
