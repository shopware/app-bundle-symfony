<?php declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

class DecorationRequiredException extends \Exception
{
    public function __construct(string $class)
    {
        parent::__construct(
            "The implementation of the interface {$class} is not within this bundle. You have to create your own implementation and decorate the service."
        );
    }
}
