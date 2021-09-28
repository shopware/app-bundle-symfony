<?php declare(strict_types=1);

namespace Shopware\AppBundle\ManifestGeneration;

use DOMDocument;
use DOMElement;

class AdminGenerator
{
    use ManifestGenerationTrait;

    public function __construct(
        private ModuleGenerator $moduleGenerator,
        private MainModuleGenerator $mainModuleGenerator,
        private ActionButtonGenerator $actionButtonGenerator
    ) {
    }

    public function generate(DOMDocument $document): DOMElement
    {
        $admin = $this->createElement($document, 'admin');

        $mainModule = $this->mainModuleGenerator->generate($document);
        $modules = $this->moduleGenerator->generate($document);
        $actionButtons = $this->actionButtonGenerator->generate($document);

        if ($mainModule) {
            $admin->appendChild($mainModule);
        }

        if (!empty($modules)) {
            $admin->append(...$modules);
        }

        if (!empty($actionButtons)) {
            $admin->append(...$actionButtons);
        }

        return $admin;
    }
}
