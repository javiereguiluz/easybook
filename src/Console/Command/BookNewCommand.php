<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Generator\BookGenerator;
use Easybook\Util\Slugger;
use Easybook\Util\Validator;
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

    /**
     * @var Validator
     */
    private $validator;

    public function __construct(
        BookGenerator $bookGenerator,
        SymfonyStyle $symfonyStyle,
        Slugger $slugger,
        Validator $validator,
        string $bookTitle
    ) {
        parent::__construct();
        $this->bookGenerator = $bookGenerator;
        $this->symfonyStyle = $symfonyStyle;
        $this->slugger = $slugger;
        $this->bookTitle = $bookTitle;
        $this->validator = $validator;
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
        $dir = $this->validator->validateDirExistsAndWritable($input->getOption('dir'));

        $bookSlug = $this->slugger->slugify($this->bookTitle);
        $bookDirectory = $dir . '/' . $bookSlug;

        $this->bookGenerator->generateToDirectory($bookDirectory);

        $this->symfonyStyle->success(
            'You can start writing your book in the following directory: ' . realpath($bookDirectory)
        );
    }
}
