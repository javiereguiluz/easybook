<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Exception\Configuration\MissingOptionException;
use Easybook\Generator\BookGenerator;
use Easybook\Util\Slugger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class BookNewCommand extends Command
{
    /**
     * @var BookGenerator
     */
    private $bookGenerator;

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

    public function __construct(
        BookGenerator $bookGenerator,
        SymfonyStyle $symfonyStyle,
        Slugger $slugger,
        string $bookTitle
    ) {
        parent::__construct();
        $this->bookGenerator = $bookGenerator;
        $this->symfonyStyle = $symfonyStyle;
        $this->slugger = $slugger;
        $this->bookTitle = $bookTitle;
    }

    /**
     * Validates that the book represented by the given $slug exists in $dir directory.
     */
    public function validateBookDir(string $slug, string $baseDir): string
    {
        $bookDir = $baseDir . DIRECTORY_SEPARATOR . $slug;

        if (! file_exists($bookDir)) {
            throw new RuntimeException(sprintf(
                "The directory of the book cannot be found.\n"
                . " Check that '%s' directory \n"
                . " has a folder named as the book slug ('%s')",
                $baseDir,
                $slug
            ));
        }

        // check that the given book already exists or ask for another slug
        while (! file_exists($bookDir)) {
            throw new RuntimeException(sprintf(
                'The given "%s" slug does not match any book in "%s" directory.',
                $slug,
                realpath($baseDir)
            ));
        }

        return $bookDir;
    }

    protected function configure(): void
    {
        $this->setName('new');
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

        $bookSlug = $this->slugger->slugify($this->bookTitle);
        $bookDirectory = $dir . '/' . $bookSlug;

        $this->bookGenerator->generateToDirectory($bookDirectory);

        $this->symfonyStyle->success(
            'You can start writing your book in the following directory: ' . realpath($bookDirectory)
        );
    }

    private function validateDirExistsAndWritable(string $dir): void
    {
        if (! is_dir($dir)) {
            // it throws an exception for invalid values because it's used in console commands
            throw new InvalidArgumentException("'${dir}' directory doesn't exist.");
        }

        if (! is_writable($dir)) {
            // it throws an exception for invalid values because it's used in console commands
            throw new InvalidArgumentException("'${dir}' directory is not writable.");
        }
    }
}
