<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Shopware\AppBundle\Exception\DOMElementCreationException;

trait ManifestGenerationTrait
{
    /**
     * @throws \DOMException
     */
    private function getTranslatableElements(DOMDocument $document, string $elementName, array $data): array
    {
        $elements = [];

        foreach ($data as $language => $translation) {
            $element = $document->createElement($elementName, $translation);

            if ($language !== 'default') {
                $element->setAttribute('lang', $language);
            }

            if ($element) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * @throws \DOMException
     * @throws DOMElementCreationException
     */
    private function createElement(DOMDocument $document, string $elementName, string $value = ''): DOMElement
    {
        $element = $document->createElement($elementName, $value);

        if (!$element) {
            throw new DOMElementCreationException($elementName);
        }

        return $element;
    }
}
