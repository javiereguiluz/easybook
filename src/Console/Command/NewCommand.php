<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Exception\Configuration\MissingOptionException;
use Easybook\Templating\Renderer;
use Easybook\Util\Slugger;
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
     * @var Slugger
     */
    private $slugger;

    /**
     * @var string
     */
    private $bookTitle;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $bookDirectory;

    /**
     * @var Renderer
     */
    private $renderer;

    public function __construct(SymfonyStyle $symfonyStyle, Slugger $slugger)
    {
        parent::__construct();
        $this->symfonyStyle = $symfonyStyle;
        $this->slugger = $slugger;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Creates a new empty book');

        $this->addArgument(Option::TITLE, null, InputOption::VALUE_REQUIRED, 'Name of your book');

        $this->addOption(Option::DIR, null, InputOption::VALUE_REQUIRED, 'Path of the documentation directory');
        $this->setHelp(file_get_contents(__DIR__ . '/Resources/BookNewCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $dir = $input->getOption(Option::DIR);
        if ($dir === null) {
            throw new MissingOptionException(sprintf('"%s" needs to be set', Option::DIR));
        }

        $this->validateDirExistsAndWritable($dir);

        $bookDirectory = $dir . DIRECTORY_SEPARATOR . $this->slugger->slugify($this->bookTitle);
        $this->generateToDirectory($bookDirectory);

        $this->symfonyStyle->success(sprintf(
            'You can start writing your book in: "%s"',
            realpath($bookDirectory)
        ));
    }

    private function generateToDirectory(string $directory): void
    {
        $this->setBookDirectory($directory);
        $this->generate();
    }

    private function setBookDirectory(string $bookDirectory): void
    {
        // check if `$bookDir` directory is available
        // if not, create a unique directory name appending a numeric suffix
        $i = 1;
        $bookDir = $bookDirectory;
        while ($this->filesystem->exists($bookDirectory)) {
            $bookDirectory = $bookDir . '-' . $i++;
        }

        $this->bookDirectory = $bookDirectory;
    }

    /**
     * Generates the hierarchy of files and directories needed to publish a book.
     */
    private function generate(): void
    {
        // why is this hardcoded? Finder?
        foreach (['chapter1.md', 'chapter2.md'] as $file) {
            $file = 'Contents/' . $file;
            $this->filesystem->copy($this->skeletonBookDirectory . '/' . $file, $this->bookDirectory . '/' . $file);
        }

        $this->renderer->renderToFile(
            $this->skeletonBookDirectory . '/config.yml.twig',
            [],
            $this->bookDirectory . '/config.yml'
        );
    }
}
