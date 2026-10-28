# App Bundle

[![PHPUnit](https://github.com/shopware/app-bundle-symfony/actions/workflows/phpunit.yml/badge.svg)](https://github.com/shopware/app-bundle-symfony/actions/workflows/phpunit.yml)
[![codecov](https://codecov.io/gh/shopware/app-bundle-symfony/branch/main/graph/badge.svg?token=B86495IQGY)](https://codecov.io/gh/shopware/app-bundle-symfony)

App Bundle integrates the [PHP App SDK](https://github.com/shopware/app-php-sdk) for Symfony.

Refer to the [quick start documentation](https://developer.shopware.com/docs/guides/plugins/apps/app-sdks/index) for more information.

## Requests to shops

All requests to shops, from the SDK's `ClientFactory` and the reachability check, go through the `shopware_app.shop_http_client` service.
It uses the options of its base client, e.g. `framework.http_client.default_options`.

```yaml
shopware_app:
    shop_http_client:
        base_client: http_client  # Symfony HTTP client to build on
        url_guard:
            enabled: false
            max_redirects: 5
            max_response_bytes: 8388608
```

Shop URLs come from whoever installs your app, so they can point into your own network.
The URL guard refuses anything but `https://` to a public IP address, also on redirects, and caps redirects and response size.
Enable it in production; local shops usually run on `http://localhost`:

```yaml
when@prod:
    shopware_app:
        shop_http_client:
            url_guard: true
```

Your own decorators of `shopware_app.shop_http_client` run before the guard with a priority below 16, and after it with a priority above 16.
