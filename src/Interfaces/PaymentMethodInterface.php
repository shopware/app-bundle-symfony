<?php declare(strict_types=1);

namespace Shopware\AppBundle\Interfaces;

interface PaymentMethodInterface
{
    public function getIdentifier(): string;

    public function getName(): array;

    public function getDescription(): array;

    public function getIconPath(): ?string;
}
