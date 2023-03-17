<?php declare(strict_types=1);

namespace Shopware\AppBundle\Attribute;

use Attribute;
use Symfony\Component\Routing\Annotation\Route;

#[Attribute(Attribute::TARGET_METHOD)]
class ActionButton extends Route
{
    private const METHODS = ['POST'];

    /**
     * @param array<string, string> $label        array with language => translation
     * @param array|string|null     $path
     * @param string[]              $requirements
     * @param string[]|string       $schemes
     */
    public function __construct(
        private string $action,
        private string $entity,
        private string $view,
        private array $label,
        private bool $openNewTab = false,
        $path = null,
        ?string $name = null,
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
        parent::__construct($path, $name, $requirements, $options, $defaults, $host, self::METHODS, $schemes, $condition, $priority, $locale, $format, $utf8, $stateless, $env);
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function getLabel(): array
    {
        return $this->label;
    }

    public function setLabel(array $label): void
    {
        $this->label = $label;
    }

    public function isOpenNewTab(): bool
    {
        return $this->openNewTab;
    }

    public function setOpenNewTab(bool $openNewTab): void
    {
        $this->openNewTab = $openNewTab;
    }
}
