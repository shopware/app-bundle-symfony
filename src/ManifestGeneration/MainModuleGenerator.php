<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Shopware\AppBundle\Exception\DOMElementCreationException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MainModuleGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private AttributeReader $attributeReader,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @throws DOMElementCreationException
     */
    public function generate(DOMDocument $document): ?DOMElement
    {
        $mainModule = $this->createElement($document, 'main-module');

        $mainModuleRoute = $this->attributeReader->getMainModule();

        if (!$mainModuleRoute) {
            return null;
        }

        $url = $this->urlGenerator->generate($mainModuleRoute->getName(), [], UrlGeneratorInterface::ABSOLUTE_URL);
        $mainModule->setAttribute('source', $url);

        return $mainModule;
    }
}
