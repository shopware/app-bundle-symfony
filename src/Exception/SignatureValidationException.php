<?php declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

class SignatureValidationException extends \Exception implements RequestExceptionInterface
{
    public function __construct(
        private RequestInterface $request
    ) {
        parent::__construct('Signature could not be verified');
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
