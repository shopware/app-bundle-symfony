<?php declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

use Exception;
use Throwable;

class DOMElementCreationException extends Exception
{
    /**
     * @var int
     */
    public $code;

    public function __construct(string $elementName, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Could not create element %s.', $elementName), $code, $previous);
    }
}
