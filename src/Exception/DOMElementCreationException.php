<?php declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

use DOMException;
use Throwable;

class DOMElementCreationException extends DOMException
{
    public function __construct(string $elementName, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Could not create element %s.', $elementName), $code, $previous);
    }
}
