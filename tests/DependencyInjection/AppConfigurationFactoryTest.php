<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\DependencyInjection\AppConfigurationFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppConfigurationFactoryTest extends TestCase
{
    public function testCreateAppFactory()
    {
        $urlGenerator = static::createMock(UrlGeneratorInterface::class);

        $urlGenerator->method('generate')->with(
            $this->anything(),
            $this->isEmpty(),
            $this->equalTo(UrlGeneratorInterface::ABSOLUTE_URL)
        )->willReturn('https://test.com/confirm');

        $factory = new AppConfigurationFactory(
            'name',
            'secret',
            'test.route',
            $urlGenerator,
            true
        );

        $config = $factory->newConfiguration();

        static::assertEquals('name', $config->getAppName());
        static::assertEquals('secret', $config->getAppSecret());
        static::assertEquals('https://test.com/confirm', $config->getRegistrationConfirmUrl());
    }
}
