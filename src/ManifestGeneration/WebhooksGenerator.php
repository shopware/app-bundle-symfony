<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WebhooksGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private AttributeReader $attributeReader,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function generate(DOMDocument $document): DOMElement
    {
        $webhooks = $this->createElement($document, 'webhooks');

        $elements = [];

        foreach ($this->attributeReader->getWebhooks() as $webhook) {
            $element = $this->createElement($document, 'webhook');

            if (!$element) {
                continue;
            }

            $element->setAttribute('name', $webhook->getName());
            $element->setAttribute('event', $webhook->getEvent());

            $url = $this->urlGenerator->generate($webhook->getName(), [], RouterInterface::ABSOLUTE_URL);
            $element->setAttribute('url', $url);

            $elements[] = $element;
        }

        $webhooks->append(...$elements);

        return $webhooks;
    }
}
