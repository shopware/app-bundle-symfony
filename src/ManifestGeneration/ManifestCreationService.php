<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use DOMNode;
use Shopware\AppBundle\Exception\DOMElementCreationException;

class ManifestCreationService
{
    use ManifestGenerationTrait;

    public function __construct(
        private SetupGenerator $setupGenerator,
        private MetaDataGenerator $metaDataGenerator,
        private PermissionsGenerator $permissionsGenerator,
        private WebhooksGenerator $webhooksGenerator,
        private AdminGenerator $adminGenerator,
        private CustomFieldsGenerator $customFieldsGenerator,
        private CookiesGenerator $cookiesGenerator,
        private PaymentsGenerator $paymentsGenerator
    ) {
    }

    /**
     * @throws DOMElementCreationException
     */
    public function generate(bool $withSecret): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->formatOutput = true;

        $manifest = $this->createElement($document, 'manifest');

        $this->appendChild($manifest, $this->setupGenerator->generate($document, $withSecret));
        $this->appendChild($manifest, $this->metaDataGenerator->generate($document));
        $this->appendChild($manifest, $this->permissionsGenerator->generate($document));
        $this->appendChild($manifest, $this->webhooksGenerator->generate($document));
        $this->appendChild($manifest, $this->adminGenerator->generate($document));
        $this->appendChild($manifest, $this->customFieldsGenerator->generate($document));
        $this->appendChild($manifest, $this->cookiesGenerator->generate($document));
        $this->appendChild($manifest, $this->paymentsGenerator->generate($document));

        $document->appendChild($manifest);

        return $document;
    }

    private function appendChild(DOMElement $element, DOMElement|DOMNode|null $child): void
    {
        if (!$child) {
            return;
        }

        $element->appendChild($child);
    }
}
