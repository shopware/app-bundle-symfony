<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMNode;
use Exception;

class CookiesGenerator
{
    public function __construct(
        private string $cookiesPath
    ) {
    }

    public function generate(DOMDocument $document): ?DOMNode
    {
        if (empty($this->cookiesPath)) {
            return null;
        }

        $tmpDocument = new DOMDocument('1.0', 'UTF-8');
        $xml = file_get_contents($this->cookiesPath);

        if (!$xml) {
            throw new Exception(sprintf('Could not read xml from %s', $this->cookiesPath));
        }

        if (!$tmpDocument->loadXML($xml)) {
            throw new Exception(sprintf('Could not load xml from %s', $this->cookiesPath));
        }

        $node = $tmpDocument->getElementsByTagName('cookies')->item(0);

        return $document->importNode($node, true);
    }
}
