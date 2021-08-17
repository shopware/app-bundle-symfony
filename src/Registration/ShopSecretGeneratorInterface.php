<?php declare(strict_types=1);

namespace Shopware\AppBundle\Registration;

interface ShopSecretGeneratorInterface
{
    public function generate(): string;
}
