<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use DOMException;

class ModuleGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private AttributeReader $attributeReader
    ) {
    }

    /**
     * @return array<DOMElement>
     * @throws DOMException
     */
    public function generate(DOMDocument $document): array
    {
        $modules = [];

        foreach ($this->attributeReader->getModules() as $module) {
            $element = $this->createElement($document, 'module');

            $element->setAttribute('name', $module->getName());
            $element->setAttribute('parent', $module->getParent());
            $element->setAttribute('position', (string) $module->getPosition());
            $element->append(...$this->getTranslatableElements($document, 'label', $module->getLabel()));

            $modules[] = $element;
        }

        return $modules;
    }
}
