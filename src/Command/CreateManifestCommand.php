<?php declare(strict_types=1);

namespace Shopware\AppBundle\Command;

use DOMDocument;
use League\Flysystem\FilesystemException;
use Shopware\AppBundle\Exception\DOMElementCreationException;
use Shopware\AppBundle\Filesystem\ProjectDirectoryProvider;
use Shopware\AppBundle\ManifestGeneration\ManifestCreationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('manifest:create', 'Command to create the manifest file.')]
class CreateManifestCommand extends Command
{
    protected static $defaultName = 'manifest:create';

    protected static $defaultDescription = 'Command to create the manifest file.';

    public function __construct(
        private ManifestCreationService $manifestCreationService,
        private ProjectDirectoryProvider $projectDirectoryAdapter,
        private string $destinationPath
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->addOption('secret', 's', InputOption::VALUE_NONE, 'Includes the secret in the manifest.xml.');
    }

    /**
     * @throws DOMElementCreationException
     * @throws FilesystemException
     * @throws \RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $withSecret = $input->getOption('secret');
        $manifest = $this->manifestCreationService->generate($withSecret);

        $this->saveToFile($manifest);

        return Command::SUCCESS;
    }

    /**
     * @throws FilesystemException
     * @throws \RuntimeException
     */
    private function saveToFile(DOMDocument $document): void
    {
        if (empty($this->destinationPath)) {
            throw new \RuntimeException('No destination path given.');
        }

        $this->projectDirectoryAdapter->getFileSystem()->write($this->destinationPath, $document->saveXML());
    }
}
