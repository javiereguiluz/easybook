<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Guard\FilesystemGuard;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symplify\PackageBuilder\Console\Command\CommandNaming;

final class NewCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FilesystemGuard
     */
    private $filesystemGuard;

    /**
     * @var string
     */
    private $skeletonBookDirectory;

    public function __construct(
        SymfonyStyle $symfonyStyle,
        FilesystemGuard $filesystemGuard,
        Filesystem $filesystem,
        string $skeletonBookDirectory
    ) {
        parent::__construct();
        $this->symfonyStyle = $symfonyStyle;
        $this->skeletonBookDirectory = $skeletonBookDirectory;
        $this->filesystemGuard = $filesystemGuard;
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Creates a new empty book to given directory');

        $this->addArgument(
            Option::BOOK_DIR,
            InputArgument::REQUIRED,
            'Directory to generate empty book to, e.g. "books/my-first-book"'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $outputDirectory = $input->getArgument(Option::BOOK_DIR);
        $this->filesystemGuard->ensureDirectoryIsEmpty($outputDirectory);

        $this->filesystem->mirror($this->skeletonBookDirectory, $outputDirectory);

        $this->symfonyStyle->success(sprintf('You can start writing your book in: "%s"', realpath($outputDirectory)));
    }
}
