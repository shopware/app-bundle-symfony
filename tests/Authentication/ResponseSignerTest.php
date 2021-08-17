<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Authentication;

use GuzzleHttp\Psr7\Response;
use Shopware\AppBundle\Authentication\ResponseSigner;
use Shopware\AppBundle\Metadata;
use Shopware\AppBundle\Shop\ShopEntity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResponseSignerTest extends KernelTestCase
{
    private MetaData $metaData;

    private ResponseSigner $requestSigner;

    private ShopEntity $shop;

    public function setUp(): void
    {
        $this->metaData = $this->getContainer()->get(Metadata::class);
        $this->requestSigner = new ResponseSigner($this->metaData);

        $this->shop = new ShopEntity(
            'a-unique-id',
            'shop.test',
            'i-am-completely-safe'
        );
    }

    public function testGetRegistrationSignature(): void
    {
        $appSecret = 'secret';

        $expectedSignature = hash_hmac(
            'sha256',
            $this->shop->getId() . $this->shop->getUrl() . $this->metaData->getName(),
            $appSecret
        );

        static::assertEquals(
            $expectedSignature,
            $this->requestSigner->getRegistrationSignature($this->shop, $appSecret)
        );
    }

    public function testSignResponse(): void
    {
        $content = 'i am a pretty message';

        $expectedSignature = hash_hmac('sha256', $content, $this->shop->getShopSecret());

        $response = new Response(200, [], $content);
        $response = $this->requestSigner->signResponse($response, $this->shop);

        static::assertNotEmpty($response->getHeader('shopware-app-signature'));
        static::assertEquals([
            $expectedSignature,
        ], $response->getHeader('shopware-app-signature'));

        // expect body is rewound
        static::assertEquals($content, $response->getBody()->getContents());
    }
}
