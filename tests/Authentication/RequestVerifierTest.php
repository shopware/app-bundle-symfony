<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Authentication;

use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Shopware\AppBundle\Authentication\RequestVerifier;
use Shopware\AppBundle\Exception\SignatureNotFoundException;
use Shopware\AppBundle\Exception\SignatureValidationException;
use Shopware\AppBundle\Shop\ShopEntity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RequestVerifierTest extends KernelTestCase
{
    private const SECRET = 'app-secret';
    private RequestVerifier $requestVerifier;

    public function setUp(): void
    {
        $this->requestVerifier = new RequestVerifier();

        $this->shop = new ShopEntity(
            'shopId',
            'shopUrl',
            self::SECRET
        );
    }

    public function testAuthenticateRegistrationRequestThrowsIfSignatureHeaderIsNotSet(): void
    {
        $request = $this->getBaseGetRequest();

        static::expectException(SignatureNotFoundException::class);
        $this->requestVerifier->authenticateRegistrationRequest($request, self::SECRET);
    }

    public function testAuthenticateRegistrationRequestThrowsIfSignatureIsInvalid(): void
    {
        $request = $this->getBaseGetRequest();

        $correctSignature = $this->hashMessage($request->getUri()->getQuery(), self::SECRET);

        $request = $request->withHeader('shopware-app-signature', $correctSignature . '-with-wrong-hash');

        static::expectException(SignatureValidationException::class);
        $this->requestVerifier->authenticateRegistrationRequest($request, self::SECRET);
    }

    public function testAuthenticateRegistrationRequestReturnsIfSignatureIsValid(): void
    {
        $request = $this->getBaseGetRequest();

        $correctSignature = $this->hashMessage($request->getUri()->getQuery(), self::SECRET);

        $request = $request->withHeader('shopware-app-signature', $correctSignature);

        static::assertNull(
            $this->requestVerifier->authenticateRegistrationRequest($request, self::SECRET)
        );
    }

    public function testAuthenticatePostRequestThrowsIfSignatureHeaderIsNotSet(): void
    {
        $request = $this->getBasePostRequest();

        static::expectException(SignatureNotFoundException::class);
        $this->requestVerifier->authenticatePostRequest($request, $this->shop);
    }

    public function testAuthenticatePostRequestThrowsIfSignatureIsInvalid(): void
    {
        $request = $this->getBasePostRequest();

        $signature = $this->hashMessage($request->getBody()->getContents(), self::SECRET);
        $request->getBody()->rewind();

        $request = $request->withHeader('shopware-shop-signature', $signature . '-with-wrong-hash');

        static::expectException(SignatureValidationException::class);
        $this->requestVerifier->authenticatePostRequest($request, $this->shop);
    }

    public function testAuthenticatePostRequestReturnsIfSignatureIsValid(): void
    {
        $request = $this->getBasePostRequest();

        $signature = $this->hashMessage($request->getBody()->getContents(), self::SECRET);
        $request->getBody()->rewind();

        $request = $request->withHeader('shopware-shop-signature', $signature);

        static::assertNull(
            $this->requestVerifier->authenticatePostRequest($request, $this->shop)
        );
    }

    public function testAuthenticateGetRequestThrowsIfSignatureHeaderIsNotSet(): void
    {
        $request = $this->getBaseGetRequest();

        static::expectException(SignatureNotFoundException::class);
        $this->requestVerifier->authenticateGetRequest($request, $this->shop);
    }

    public function testAuthenticateGetRequestThrowsIfSignatureIsInvalid(): void
    {
        $request = $this->getBaseGetRequest();

        $correctSignature = $this->hashMessage($request->getUri()->getQuery(), self::SECRET);
        $wrongSignature = $correctSignature . '-with-wrong-data';

        $request = $request->withUri(
            (new Uri(''))
            ->withQuery($request->getUri()->getQuery() . "&shopware-shop-signature={$wrongSignature}")
        );

        static::expectException(SignatureValidationException::class);
        $this->requestVerifier->authenticateGetRequest($request, $this->shop);
    }

    public function testAuthenticateGetRequestReturnsIfSignatureIsValid(): void
    {
        $request = $this->getBaseGetRequest();

        $correctSignature = $this->hashMessage($request->getUri()->getQuery(), self::SECRET);

        $request = $request->withUri(
            (new Uri(''))
            ->withQuery($request->getUri()->getQuery() . "&shopware-shop-signature={$correctSignature}")
        );

        static::assertNull($this->requestVerifier->authenticateGetRequest($request, $this->shop));
    }

    private function hashMessage(string $message, string $secret): string
    {
        return hash_hmac('sha256', $message, $secret);
    }

    private function getBaseGetRequest(): RequestInterface
    {
        $uri = (new Uri(''))
            ->withQuery(Query::build([
                'shop-id' => '1',
                'shop-url' => 'test.shop',
                'timestamp' => 'today',
            ]));

        return new Request('GET', $uri);
    }

    private function getBasePostRequest(): RequestInterface
    {
        return new Request('POST', new Uri(''), [], 'this is a nice message');
    }
}
