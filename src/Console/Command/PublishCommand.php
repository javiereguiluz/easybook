<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Book\Book;
use Easybook\Book\Edition;
use Easybook\Book\Provider\BookProvider;
use Easybook\Book\Provider\CurrentEditionProvider;
use Easybook\Configuration\Option;
use Easybook\Exception\Process\BeforeOrAfterPublishScriptFailedException;
use Easybook\Guard\FilesystemGuard;
use Easybook\Publisher\PublisherProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class PublishCommand extends Command
{
    /**
     * @var PublisherProvider
     */
    private $publisherProvider;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    /**
     * @var FilesystemGuard
     */
    private $filesystemGuard;

    /**
     * @var BookProvider
     */
    private $bookProvider;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CurrentEditionProvider
     */
    private $currentEditionProvider;

    public function __construct(
        PublisherProvider $publisherProvider,
        SymfonyStyle $symfonyStyle,
        ParameterProvider $parameterProvider,
        FilesystemGuard $filesystemGuard,
        BookProvider $bookProvider,
        Filesystem $filesystem,
        CurrentEditionProvider $currentEditionProvider
    ) {
        parent::__construct();

        $this->publisherProvider = $publisherProvider;
        $this->symfonyStyle = $symfonyStyle;
        $this->parameterProvider = $parameterProvider;
        $this->filesystemGuard = $filesystemGuard;
        $this->bookProvider = $bookProvider;
        $this->filesystem = $filesystem;
        $this->currentEditionProvider = $currentEditionProvider;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Publishes book editions.');
        $this->addArgument(Option::BOOK_DIR, InputArgument::REQUIRED, 'Path to book directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bookDirectory = $input->getArgument(Option::BOOK_DIR);

        $this->filesystemGuard->ensureBookDirectoryExists($bookDirectory);

        $this->parameterProvider->changeParameter('source_book_dir', $bookDirectory);
        $this->parameterProvider->changeParameter('book_resources_dir', $bookDirectory . '/Resources');
        $this->parameterProvider->changeParameter('book_templates_dir', $bookDirectory . '/Resources/Templates');

        $book = $this->bookProvider->provide();

        foreach ($book->getEditions() as $edition) {
            $this->publishEdition($bookDirectory, $edition, $book);
        }

        $this->symfonyStyle->success('Book was published');

        // success
        return 0;
    }

    /**
     * @param string[] $scripts
     */
    private function runScripts(array $scripts, string $workingDirectory): void
    {
        foreach ($scripts as $script) {
            $process = new Process($script, $workingDirectory);
            $process->run();

            if ($process->isSuccessful()) {
                $this->symfonyStyle->success($process->getOutput());
            } else {
                throw new BeforeOrAfterPublishScriptFailedException(sprintf(
                    'Executing script "%s" failed in "%s" directory: "%s"',
                    $script,
                    $workingDirectory,
                    $process->getErrorOutput()
                ));
            }
        }
    }

    private function publishEdition(string $bookDirectory, Edition $edition, Book $book): void
    {
        $bookEditionDirectory = $bookDirectory . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . $edition->getFormat();
        $this->filesystem->mkdir($bookEditionDirectory);

        $this->currentEditionProvider->setEdition($edition->getFormat());

        $this->runScripts($edition->getBeforePublishScripts(), $bookEditionDirectory);

        $this->symfonyStyle->note(sprintf(
            'Publishing <comment>%s</comment> edition of <info>%s</info> book...',
            $edition->getFormat(),
            $book->getName()
        ));

        $publisher = $this->publisherProvider->provideByFormat($edition->getFormat());
        $publisher->publishBook();

        $this->runScripts($edition->getAfterPublishScripts(), $bookEditionDirectory);

        $this->symfonyStyle->success(sprintf('You can access the book in: "%s"', realpath($bookEditionDirectory)));
    }
}
