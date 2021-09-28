<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;

class PermissionsGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private array $permissions
    ) {
    }

    public function generate(DOMDocument $document): DOMElement
    {
        $elements = [];

        $permissions = $document->createElement('permissions');

        foreach ($this->permissions as $permission => $entities) {
            foreach ($entities as $entity) {
                $elements[] = $this->createElement($document, $permission, $entity);
            }
        }

        $permissions->append(...$elements);

        return $permissions;
    }
}
