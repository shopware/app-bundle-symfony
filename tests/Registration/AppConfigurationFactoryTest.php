<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\Registration;

use Shopware\AppBundle\Registration\AppConfigurationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppConfigurationFactoryTest extends TestCase
{
    public function testCreateAppFactory()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $urlGenerator->method('generate')->with(
            $this->anything(),
            $this->isEmpty(),
            $this->equalTo(UrlGeneratorInterface::ABSOLUTE_URL)
        )->willReturn('https://test.com/confirm');

        $factory = new AppConfigurationFactory(
            'name',
            'secret',
            'test.route',
            $urlGenerator
        );

        $config = $factory->newConfiguration();

        $this->assertEquals('name', $config->getAppName());
        $this->assertEquals('secret', $config->getAppSecret());
        $this->assertEquals('https://test.com/confirm', $config->getRegistrationConfirmUrl());
    }
}
