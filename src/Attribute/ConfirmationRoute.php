<?php declare(strict_types=1);

namespace Shopware\AppBundle\Attribute;

use Attribute;
use Symfony\Component\Routing\Annotation\Route;

#[Attribute(Attribute::TARGET_METHOD)]
class ConfirmationRoute extends Route
{
    private const METHODS = ['POST'];

    /**
     * @param array|string      $data         data array managed by the Doctrine Annotations library or the path
     * @param array|string|null $path
     * @param string[]|string   $schemes
     */
    public function __construct(
        string $name,
        $data = [],
        $path = null,
        array $requirements = [],
        array $options = [],
        array $defaults = [],
        ?string $host = null,
        $schemes = [],
        ?string $condition = null,
        ?int $priority = null,
        ?string $locale = null,
        ?string $format = null,
        ?bool $utf8 = null,
        ?bool $stateless = null,
        ?string $env = null
    ) {
        parent::__construct($data, $path, $name, $requirements, $options, $defaults, $host, self::METHODS, $schemes, $condition, $priority, $locale, $format, $utf8, $stateless, $env);
    }
}
