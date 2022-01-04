<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Shopware\AppBundle\Exception\DOMElementCreationException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebhooksGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private AttributeReader $attributeReader,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @throws \DOMException
     * @throws DOMElementCreationException
     */
    public function generate(DOMDocument $document): DOMElement
    {
        $webhooks = $this->createElement($document, 'webhooks');

        $elements = [];

        foreach ($this->attributeReader->getWebhooks() as $webhook) {
            try {
                $element = $this->createElement($document, 'webhook');
            } catch (DOMElementCreationException) {
                continue;
            }

            $element->setAttribute('name', $webhook->getName());
            $element->setAttribute('event', $webhook->getEvent());

            $url = $this->urlGenerator->generate($webhook->getName(), [], UrlGeneratorInterface::ABSOLUTE_URL);
            $element->setAttribute('url', $url);

            $elements[] = $element;
        }

        $webhooks->append(...$elements);

        return $webhooks;
    }
}
