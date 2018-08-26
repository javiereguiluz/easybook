<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Exception\Filesystem\DirectoryNotEmptyException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symplify\PackageBuilder\Console\Command\CommandNaming;

final class NewCommand extends Command
{
    /**
     * @var string
     */
    private $skeletonBookDirectory;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(SymfonyStyle $symfonyStyle, Filesystem $filesystem, string $skeletonBookDirectory)
    {
        parent::__construct();
        $this->symfonyStyle = $symfonyStyle;
        $this->filesystem = $filesystem;
        $this->skeletonBookDirectory = $skeletonBookDirectory;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Creates a new empty book to given directory');

        $this->addArgument(
            Option::DIR,
            InputOption::VALUE_REQUIRED,
            'Directory to generate empty book to, e.g. "books/my-first-book"'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $outputDirectory = $input->getArgument(Option::DIR);

        $this->ensureDirectoryIsEmpty($outputDirectory);

        $this->filesystem->mirror($this->skeletonBookDirectory, $outputDirectory);

        $this->symfonyStyle->success(sprintf('You can start writing your book in: "%s"', realpath($outputDirectory)));
    }

    private function ensureDirectoryIsEmpty(string $directory): void
    {
        if (! $this->filesystem->exists($directory)) {
            return;
        }

        if ((bool) glob($directory . '/*')) {
            throw new DirectoryNotEmptyException(sprintf(
                'Directory "%s" for new book is not empty. Delete it or choose a new one.',
                $directory
            ));
        }
    }
}
