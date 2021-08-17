<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ShopwareAppBundleTest extends KernelTestCase
{
    public function testDefaultBundleConfiguration(): void
    {
        $metaData = $this->getContainer()->getParameter('shopware_app.metadata');
        $appSecret = $this->getContainer()->getParameter('shopware_app.setup.secret');

        static::assertEquals([
            'version' => '1.0.0',
            'name' => 'myAppName',
            'label' => [
                'default' => 'english label',
                'de-DE' => 'german label',
            ],
            'description' => [
                'default' => 'english description',
                'de-DE' => 'german description',
            ],
            'author' => 'shopware AG',
            'copyright' => '(c) by shopware AG',
            'icon' => 'image.png',
            'license' => 'MIT',
            'privacy' => 'privacy',
        ], $metaData);

        static::assertEquals('myAppSecret', $appSecret);
    }
}
