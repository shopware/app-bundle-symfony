<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Shopware\AppBundle\Exception\DOMElementCreationException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActionButtonGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private AttributeReader $attributeReader,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @return array<DOMElement>
     * @throws DOMElementCreationException
     */
    public function generate(DOMDocument $document): array
    {
        $actionButtons = [];

        foreach ($this->attributeReader->getActionButtons() as $actionButton) {
            $element = $this->createElement($document, 'action-button');

            $url = $this->urlGenerator->generate($actionButton->getName(), [], UrlGeneratorInterface::ABSOLUTE_URL);
            $openNewTab = $actionButton->isOpenNewTab() ? 'true' : 'false';

            $element->append(...$this->getTranslatableElements($document, 'label', $actionButton->getLabel()));
            $element->setAttribute('action', $actionButton->getAction());
            $element->setAttribute('entity', $actionButton->getEntity());
            $element->setAttribute('view', $actionButton->getView());
            $element->setAttribute('url', $url);
            $element->setAttribute('openNewTab', $openNewTab);

            $actionButtons[] = $element;
        }

        return $actionButtons;
    }
}
