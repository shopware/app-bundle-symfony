<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Shopware\AppBundle\Exception\DOMElementCreationException;
use Shopware\AppBundle\Metadata;

class MetaDataGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private Metadata $metadata
    ) {
    }

    /**
     * @throws \DOMException
     * @throws DOMElementCreationException
     */
    public function generate(DOMDocument $document): DOMElement
    {
        $elements = [];

        $metadata = $this->createElement($document, 'meta');
        $elements[] = $this->createElement($document, 'name', $this->metadata->getName());
        $elements[] = $this->createElement($document, 'author', $this->metadata->getAuthor());
        $elements[] = $this->createElement($document, 'copyright', $this->metadata->getCopyright());
        $elements[] = $this->createElement($document, 'version', $this->metadata->getVersion());
        $elements[] = $this->createElement($document, 'license', $this->metadata->getLicense());

        if ($this->metadata->icon()) {
            $elements[] = $this->createElement($document, 'icon', $this->metadata->icon());
        }

        if ($this->metadata->getPrivacy()) {
            $elements[] = $this->createElement($document, 'privacy', $this->metadata->getPrivacy());
        }

        $metadata->append(...$elements);
        $metadata->append(...$this->getTranslatableElements($document, 'label', $this->metadata->getLabel()));
        $metadata->append(...$this->getTranslatableElements($document, 'description', $this->metadata->getDescription()));
        $metadata->append(...$this->getTranslatableElements($document, 'privacyPolicyExtensions', $this->metadata->getPrivacyPolicyExtensions()));

        return $metadata;
    }
}
