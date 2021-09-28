<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SetupGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private string $secret,
        private AttributeReader $attributeReader,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function generate(DOMDocument $document, bool $withSecret): DOMElement
    {
        $setup = $this->createElement($document, 'setup');

        $registrationRoute = $this->urlGenerator->generate($this->attributeReader->getRegistrationRoute()->getName(), [], RouterInterface::ABSOLUTE_URL);
        $registrationUrl = $this->createElement($document, 'registrationUrl', $registrationRoute);

        if ($withSecret) {
            $secret = $this->createElement($document, 'secret', $this->secret);
            $setup->appendChild($secret);
        }

        $setup->appendChild($registrationUrl);

        return $setup;
    }
}
