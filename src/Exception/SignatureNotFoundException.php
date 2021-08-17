<?php declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

class SignatureNotFoundException extends \Exception implements RequestExceptionInterface
{
    private RequestInterface $request;

    public function __construct(RequestInterface $request)
    {
        parent::__construct('Signature is not present in request');

        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
