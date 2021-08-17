<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test;

use Shopware\AppBundle\Metadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MetadataTest extends KernelTestCase
{
    public function testMetadataIsCreatedFromConfiguration(): void
    {
        $metadataConfiguration = $this->getContainer()->getParameter('shopware_app.metadata');

        /** @var Metadata $metadataStruct */
        $metadataStruct = $this->getContainer()->get(Metadata::class);

        static::assertInstanceOf(Metadata::class, $metadataStruct);

        static::assertEquals($metadataConfiguration['name'], $metadataStruct->getName());
        static::assertEquals($metadataConfiguration['version'], $metadataStruct->getVersion());
        static::assertEquals($metadataConfiguration['label'], $metadataStruct->getLabel());
        static::assertEquals($metadataConfiguration['description'], $metadataStruct->getDescription());
        static::assertEquals($metadataConfiguration['author'], $metadataStruct->getAuthor());
        static::assertEquals($metadataConfiguration['copyright'], $metadataStruct->getCopyright());
        static::assertEquals($metadataConfiguration['icon'], $metadataStruct->icon());
        static::assertEquals($metadataConfiguration['license'], $metadataStruct->getLicense());
        static::assertEquals($metadataConfiguration['privacy'], $metadataStruct->getPrivacy());
    }
}
