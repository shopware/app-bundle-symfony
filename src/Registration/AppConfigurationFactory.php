<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Registration;

use Shopware\App\SDK\AppConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppConfigurationFactory
{
    public function __construct(
        private readonly string $appName,
        private readonly string $appSecret,
        private readonly string $shopwareAppConfirmUrl,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function newConfiguration(): AppConfiguration
    {
        return new AppConfiguration(
            $this->appName,
            $this->appSecret,
            $this->urlGenerator->generate($this->shopwareAppConfirmUrl, [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }
}
