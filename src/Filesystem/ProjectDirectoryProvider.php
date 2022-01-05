<?php declare(strict_types=1);

namespace Shopware\AppBundle\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class ProjectDirectoryProvider
{
    public function __construct(private string $projectDir)
    {
    }

    public function getFileSystem(): FilesystemOperator
    {
        return new Filesystem(
            new LocalFilesystemAdapter(
                $this->projectDir,
                PortableVisibilityConverter::fromArray([
                    'file' => ['public' => 0755, 'private' => 0755],
                    'dir' => ['public' => 0755, 'private' => 0755],
                ]),
                LOCK_EX,
                LocalFilesystemAdapter::DISALLOW_LINKS
            )
        );
    }
}
