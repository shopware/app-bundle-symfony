<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;
use Shopware\AppBundle\Exception\DOMElementCreationException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SetupGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private string $secret,
        private AttributeReader $attributeReader,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @throws DOMElementCreationException
     * @throws \Exception
     */
    public function generate(DOMDocument $document, bool $withSecret): DOMElement
    {
        $setup = $this->createElement($document, 'setup');

        $registrationAttribute = $this->attributeReader->getRegistrationRoute();
        if ($registrationAttribute === null) {
            throw new \RuntimeException('No registration route could be found.');
        }
        $registrationRoute = $this->urlGenerator->generate(
            $registrationAttribute->getName(),
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $registrationUrl = $this->createElement($document, 'registrationUrl', $registrationRoute);

        if ($withSecret) {
            $secret = $this->createElement($document, 'secret', $this->secret);
            $setup->appendChild($secret);
        }

        $setup->appendChild($registrationUrl);

        return $setup;
    }
}
