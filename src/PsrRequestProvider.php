<?php

declare(strict_types=1);

namespace Shopware\AppBundle;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Converts a Symfony request into a PSR-7 request once and caches it on the request attributes,
 * so that every consumer shares the same instance and the same rewindable body stream.
 *
 * A JSON body is guaranteed to be decoded into the parsed body, and is decoded exactly once.
 */
class PsrRequestProvider
{
    public function __construct(
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
    ) {
    }

    /**
     * @throws JsonException if the request announces JSON but the body does not decode to an array
     */
    public function get(Request $request): ServerRequestInterface
    {
        $psrRequest = $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE);

        if ($psrRequest instanceof ServerRequestInterface) {
            return $psrRequest;
        }

        $psrRequest = $this->httpMessageFactory->createRequest($request);
        $psrRequest = $this->withJsonBody($psrRequest, $request);

        $request->attributes->set(AppRequest::PSR_REQUEST_ATTRIBUTE, $psrRequest);

        return $psrRequest;
    }

    /**
     * Symfony compares the content type exactly, but Shopware sends a comma separated list of content types
     * for some requests (e.g. flow actions). This method ensures that a JSON body is decoded into the parsed body exactly once.
     *
     * @throws JsonException
     */
    private function withJsonBody(ServerRequestInterface $psrRequest, Request $request): ServerRequestInterface
    {
        if ($request->getContent() === '' || !str_contains((string) $request->headers->get('Content-Type'), 'application/json')) {
            return $psrRequest;
        }

        if ($request->getContentTypeFormat() === 'json') {
            if ($psrRequest->getParsedBody() === null) {
                throw new JsonException('Could not decode request body.');
            }

            return $psrRequest;
        }

        return $psrRequest->withParsedBody($request->getPayload()->all());
    }
}
