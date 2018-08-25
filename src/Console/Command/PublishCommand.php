<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Publishers\PublisherProvider;
use Easybook\Util\Validator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class PublishCommand extends Command
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $bookTitle;

    /**
     * @var Validator
     */
    private $validator;

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

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        string $bookTitle,
        Validator $validator,
        PublisherProvider $publisherProvider,
        SymfonyStyle $symfonyStyle,
        ParameterProvider $parameterProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->bookTitle = $bookTitle;
        $this->validator = $validator;
        $this->publisherProvider = $publisherProvider;

        parent::__construct();
        $this->symfonyStyle = $symfonyStyle;
        $this->parameterProvider = $parameterProvider;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Publishes an edition of a book');
        $this->addArgument(Option::SLUG, InputArgument::REQUIRED, '');
        $this->addOption(Option::DIR, '', InputOption::VALUE_REQUIRED, 'Path of the documentation directory');

        $this->setHelp(file_get_contents(__DIR__ . '/Resources/BookPublishCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $slug = $input->getArgument('slug');
        $outputDir = $input->getOption('dir'); // ?: $this->app['app.dir.doc'];

        // validate book dir and add some useful values to the app configuration
        $bookDir = $this->validator->validateBookDir($slug, $outputDir);

        $this->parameterProvider->changeParameter('source_book_dir', $bookDir);
        $this->parameterProvider->changeParameter('book_resources_dir', $bookDir . '/Resources');
        $this->parameterProvider->changeParameter('book_templates_dir', $bookDir . '/Resources/Templates');

        // all parameters are loaded here...

        // execute the 'before_publish' scripts
        $this->runScripts((array) $this->parameterProvider->provideParameter('before_publish'), $outputDir);

        // book publishing starts
        $this->eventDispatcher->dispatch(Events::PRE_PUBLISH, new Event());

        // just one edition?

        $this->symfonyStyle->note(sprintf(
            'Publishing <comment>%s</comment> edition of <info>%s</info> book...',
            $edition,
            $this->bookTitle
        ));

        // @todo foreach book editions here

        foreach ($this->publisherProvider->getPublishers() as $publisher) {
            $publisher->publishBook();
        }

        $this->eventDispatcher->dispatch(Events::POST_PUBLISH, new Event());

        $this->runScripts((array) $this->parameterProvider->provideParameter('after_publish'), $outputDir);

        $this->symfonyStyle->success(
            'You can access the book in:' .
            realpath($this->app['publishing.dir.output'])
        );
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
