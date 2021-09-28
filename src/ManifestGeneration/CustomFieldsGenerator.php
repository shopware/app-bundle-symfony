<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMNode;
use Exception;

class CustomFieldsGenerator
{
    public function __construct(
        private string $customFieldsPath
    ) {
    }

    public function generate(DOMDocument $document): ?DOMNode
    {
        if (empty($this->customFieldsPath)) {
            return null;
        }

        $tmpDocument = new DOMDocument('1.0', 'UTF-8');

        $xml = file_get_contents($this->customFieldsPath);

        if (!$xml) {
            throw new Exception(sprintf('Could not read xml from %s', $this->customFieldsPath));
        }

        if (!$tmpDocument->loadXML($xml)) {
            throw new Exception(sprintf('Could not load xml from %s', $this->customFieldsPath));
        }

        $node = $tmpDocument->getElementsByTagName('custom-fields')->item(0);

        return $document->importNode($node, true);
    }
}
