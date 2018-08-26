<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Filesystem\FilesystemGuard;
use Easybook\Publishers\PublisherProvider;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class PublishCommand extends Command
{
    /**
     * @var string
     */
    private const BEFORE_PUBLISH = 'before_publish';

    /**
     * @var string
     */
    private const AFTER_PUBLISH = 'after_publish';

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

    public function __construct(
        PublisherProvider $publisherProvider,
        SymfonyStyle $symfonyStyle,
        ParameterProvider $parameterProvider,
        FilesystemGuard $filesystemGuard
    ) {
        parent::__construct();

        $this->publisherProvider = $publisherProvider;
        $this->symfonyStyle = $symfonyStyle;
        $this->parameterProvider = $parameterProvider;
        $this->filesystemGuard = $filesystemGuard;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Publishes book editions.');
        $this->addArgument(Option::BOOK_DIR, InputArgument::REQUIRED, 'Path to book directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $bookDirectory = $input->getArgument(Option::BOOK_DIR);

        $this->filesystemGuard->ensureBookDirectoryExists($bookDirectory);

        $this->parameterProvider->changeParameter('source_book_dir', $bookDirectory);
        $this->parameterProvider->changeParameter('book_resources_dir', $bookDirectory . '/Resources');
        $this->parameterProvider->changeParameter('book_templates_dir', $bookDirectory . '/Resources/Templates');

        // all parameters are loaded here...

        // execute the 'before_publish' scripts
        $this->runScripts((array) $this->parameterProvider->provideParameter(self::BEFORE_PUBLISH), $bookDirectory);

//        dump('EEE');
//        die;
//
//        // create book
//
//        /** @var Book $book */
//        foreach ($book->getEditions() as $edition) {
//            // ...
//        }

        dump($this->parameterProvider->provideParameter(self::BEFORE_PUBLISH));

        die;

        $this->symfonyStyle->note(sprintf(
            'Publishing <comment>%s</comment> edition of <info>%s</info> book...',
            $edition,
            $book->getTitle()
        ));

        // @todo foreach book editions here

        foreach ($this->publisherProvider->getPublishers() as $publisher) {
            $publisher->publishBook();
        }

        $this->runScripts((array) $this->parameterProvider->provideParameter(self::AFTER_PUBLISH), $bookDirectory);

        $this->symfonyStyle->success(sprintf(
            'You can access the book in: "%s"',
            realpath($this->app['publishing.dir.output'])
        ));
    }

    /**
     * @param string[] $scripts
     */
    private function runScripts(array $scripts, string $outputDir): void
    {
        foreach ($scripts as $script) {
            $process = new Process($script, $outputDir);
            $process->run();

            if ($process->isSuccessful()) {
                $this->symfonyStyle->success($process->getOutput());
            } else {
                throw new RuntimeException(sprintf(
                    'Executing script "%s" failed: "%s"',
                    $script . PHP_EOL,
                    $process->getErrorOutput()
                ));
            }
        }
    }
}
