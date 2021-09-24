<?php declare(strict_types=1);

namespace Shopware\AppBundle\Attribute;

use Attribute;
use Symfony\Component\Routing\Annotation\Route;

#[Attribute(Attribute::TARGET_METHOD)]
class PaymentRoute extends Route
{
}
