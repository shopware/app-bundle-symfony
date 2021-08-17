<?php declare(strict_types=1);

namespace Shopware\AppBundle;

class Metadata
{
    use ArrayAssignableTrait;

    private string $name;

    /**
     * @var array<string, string>
     */
    private array $label;

    /**
     * @var array<string, string>
     */
    private array $description;

    private string $author;

    private string $copyright;

    private string $version;

    private string $license;

    private ?string $icon = null;

    private ?string $privacy = null;

    /**
     * @var array<string, string>
     */
    private $privacyPolicyExtensions = [];

    public static function fromArray(array $data): self
    {
        return (new self())->assign($data);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): array
    {
        return $this->label;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getCopyright(): string
    {
        return $this->copyright;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getLicense(): string
    {
        return $this->license;
    }

    public function icon(): ?string
    {
        return $this->icon;
    }

    public function getPrivacy(): ?string
    {
        return $this->privacy;
    }

    public function getPrivacyPolicyExtensions(): array
    {
        return $this->privacyPolicyExtensions;
    }
}
