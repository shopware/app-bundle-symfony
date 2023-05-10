<?php

declare(strict_types=1);

namespace Shopware\AppBundle;

final class AppRequest
{
    final public const SHOP_ATTRIBUTE = '_shopware_app_shop';
    final public const PSR_REQUEST_ATTRIBUTE = '_shopware_app_psr_request';
    final public const SIGN_RESPONSE = '_shopware_app_sign_response';
}
