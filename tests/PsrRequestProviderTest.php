<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request as PsrRequest;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\AppRequest;
use Shopware\AppBundle\PsrRequestProvider;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(PsrRequestProvider::class)]
class PsrRequestProviderTest extends TestCase
{
    public function testCachedPsrRequestIsReused(): void
    {
        $psrRequest = new ServerRequest('POST', 'http://localhost');
        $request = new Request(attributes: [AppRequest::PSR_REQUEST_ATTRIBUTE => $psrRequest]);

        $factory = static::createMock(HttpMessageFactoryInterface::class);
        $factory->expects(static::never())->method('createRequest');

        static::assertSame($psrRequest, (new PsrRequestProvider($factory))->get($request));
    }

    public static function provideNonServerRequestAttributes(): \Generator
    {
        yield 'no attribute' => [null];
        yield 'garbage' => [false];
        yield 'symfony request' => [new Request()];
        // A plain PSR request is a RequestInterface but not a ServerRequestInterface,
        // so it cannot satisfy the return type and has to be converted again.
        yield 'psr request' => [new PsrRequest('POST', 'http://localhost')];
    }

    #[DataProvider('provideNonServerRequestAttributes')]
    public function testAttributeIsReplacedWhenItIsNoServerRequest(mixed $attribute): void
    {
        $request = Request::create('http://localhost/', 'GET', content: '');
        $request->attributes->set(AppRequest::PSR_REQUEST_ATTRIBUTE, $attribute);

        $psrRequest = $this->createProvider()->get($request);

        static::assertInstanceOf(ServerRequest::class, $psrRequest);
        static::assertSame($psrRequest, $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE));
    }

    public function testRequestIsConvertedOnlyOnceAndStoredOnTheAttributes(): void
    {
        $request = Request::create('http://localhost/');
        $psrRequest = new ServerRequest('GET', 'http://localhost/');

        $factory = static::createMock(HttpMessageFactoryInterface::class);
        $factory->expects(static::once())
            ->method('createRequest')
            ->with(static::identicalTo($request))
            ->willReturn($psrRequest);

        $provider = new PsrRequestProvider($factory);

        static::assertSame($psrRequest, $provider->get($request));
        static::assertSame($psrRequest, $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE));
        static::assertSame($psrRequest, $provider->get($request));
    }

    public function testSymfonyAttributesAreCarriedOver(): void
    {
        $request = Request::create('http://localhost/', 'GET', content: '');
        $request->attributes->set('shopware-app-context', 'test');

        static::assertSame('test', $this->createProvider()->get($request)->getAttribute('shopware-app-context'));
    }

    public function testValidJsonBodyIsAcceptedAndKeptReadable(): void
    {
        $request = self::createJsonRequest('{"source":{"shopId":"123"}}');

        $psrRequest = $this->createProvider()->get($request);

        // The SDK reads the raw stream, so the guard must not consume it.
        static::assertSame('{"source":{"shopId":"123"}}', (string) $psrRequest->getBody());
        static::assertSame($psrRequest, $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE));
    }

    public function testPsrHttpFactoryDecodesTheJsonBodyForUs(): void
    {
        $provider = $this->createProvider();

        static::assertSame(
            ['source' => ['shopId' => '123']],
            $provider->get(self::createJsonRequest('{"source":{"shopId":"123"}}'))->getParsedBody()
        );

        $factory = new PsrHttpFactory(new Psr17Factory(), new Psr17Factory(), new Psr17Factory(), new Psr17Factory());

        static::assertNull($factory->createRequest(self::createJsonRequest('{"source":'))->getParsedBody());
    }

    public function testMalformedJsonBodyIsRejected(): void
    {
        $request = self::createJsonRequest('{"source":');

        try {
            $this->createProvider()->get($request);
        } catch (JsonException $e) {
            static::assertSame('Could not decode request body.', $e->getMessage());
            // RequestExceptionInterface is what makes HttpKernel answer with a 400 instead of a 500.
            static::assertInstanceOf(RequestExceptionInterface::class, $e);
            static::assertFalse($request->attributes->has(AppRequest::PSR_REQUEST_ATTRIBUTE));

            return;
        }

        static::fail(sprintf('Expected %s to be thrown.', JsonException::class));
    }

    public static function provideNonArrayJsonBodies(): \Generator
    {
        yield 'string' => ['"a string"'];
        yield 'int' => ['42'];
        yield 'bool' => ['true'];
        yield 'null literal' => ['null'];
    }

    /**
     * Mirrors Request::getPayload(), which also refuses a body that is valid JSON but not an array.
     */
    #[DataProvider('provideNonArrayJsonBodies')]
    public function testJsonBodyThatIsNoArrayIsRejected(string $content): void
    {
        $this->expectException(JsonException::class);

        $this->createProvider()->get(self::createJsonRequest($content));
    }

    public function testEmptyJsonBodyIsAccepted(): void
    {
        $request = self::createJsonRequest('');

        $psrRequest = $this->createProvider()->get($request);

        static::assertSame($psrRequest, $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE));
    }

    public static function provideNonJsonRequests(): \Generator
    {
        yield 'form encoded' => ['application/x-www-form-urlencoded', 'foo=bar'];
        yield 'plain text holding broken json' => ['text/plain', '{"source":'];
    }

    #[DataProvider('provideNonJsonRequests')]
    public function testNonJsonRequestsAreNeverRejected(string $contentType, string $content): void
    {
        $request = Request::create('http://localhost/', 'POST', server: ['CONTENT_TYPE' => $contentType], content: $content);

        $psrRequest = $this->createProvider()->get($request);

        static::assertSame($psrRequest, $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE));
    }

    public function testCommaSeparatedContentTypeStillGetsAParsedBody(): void
    {
        $request = self::createCommaSeparatedJsonRequest('{"source":{"shopId":"123"}}');

        $psrRequest = $this->createProvider()->get($request);

        static::assertSame(['source' => ['shopId' => '123']], $psrRequest->getParsedBody());
        // Decoding it must not consume the stream the SDK reads today.
        static::assertSame('{"source":{"shopId":"123"}}', (string) $psrRequest->getBody());
    }

    public function testMalformedJsonBodyWithCommaSeparatedContentTypeIsRejected(): void
    {
        $request = self::createCommaSeparatedJsonRequest('{"source":');

        try {
            $this->createProvider()->get($request);
        } catch (JsonException $e) {
            static::assertSame('Could not decode request body.', $e->getMessage());
            static::assertInstanceOf(RequestExceptionInterface::class, $e);
            static::assertFalse($request->attributes->has(AppRequest::PSR_REQUEST_ATTRIBUTE));

            return;
        }

        static::fail(sprintf('Expected %s to be thrown.', JsonException::class));
    }

    #[DataProvider('provideNonArrayJsonBodies')]
    public function testCommaSeparatedContentTypeWithBodyThatIsNoArrayIsRejected(string $content): void
    {
        $this->expectException(JsonException::class);

        $this->createProvider()->get(self::createCommaSeparatedJsonRequest($content));
    }

    public function testRequestWithoutContentTypeIsNeverRejected(): void
    {
        $request = new Request(server: ['HTTP_HOST' => 'localhost'], content: '{"source":');

        $psrRequest = $this->createProvider()->get($request);

        static::assertSame($psrRequest, $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE));
    }

    private static function createJsonRequest(string $content): Request
    {
        return Request::create('http://localhost/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: $content);
    }

    private static function createCommaSeparatedJsonRequest(string $content): Request
    {
        return Request::create('http://localhost/', 'POST', server: ['CONTENT_TYPE' => 'application/json, application/json'], content: $content);
    }

    private function createProvider(): PsrRequestProvider
    {
        $psr17Factory = new Psr17Factory();

        return new PsrRequestProvider(new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory));
    }
}
