<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Publishers\PublisherProvider;
use Easybook\Util\Validator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class BookPublishCommand extends Command
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
        $this->setName('publish');
        $this->setDescription('Publishes an edition of a book');
        $this->addOption(Option::DIR, '', InputOption::VALUE_OPTIONAL, 'Path of the documentation directory');

        $this->setHelp(file_get_contents(__DIR__ . '/Resources/BookPublishCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $slug = $input->getArgument('slug');
        $dir = $input->getOption('dir') ?: $this->app['app.dir.doc'];

        // validate book dir and add some useful values to the app configuration
        $bookDir = $this->validator->validateBookDir($slug, $dir);

        $this->app['publishing.dir.book'] = $bookDir;
        $this->app['publishing.dir.contents'] = $bookDir . '/Contents';
        $this->app['publishing.dir.resources'] = $bookDir . '/Resources';
        $this->app['publishing.dir.plugins'] = $bookDir . '/Resources/Plugins';
        $this->app['publishing.dir.templates'] = $bookDir . '/Resources/Templates';

        // all parameters are loaded here...

        // execute the 'before_publish' scripts
//        $this->runScripts( $this->app->edition('before_publish'));
        $this->runScripts((array) $this->parameterProvider->provideParameter('before_publish'));

        // book publishing starts
        $this->eventDispatcher->dispatch(Events::PRE_PUBLISH, new Event());

        $this->symfonyStyle->note(sprintf(
            'Publishing <comment>%s</comment> edition of <info>%s</info> book...',
            $edition,
            $this->bookTitle
        ));

        // @todo foreach book editions here

        foreach ($this->publisherProvider->getPublishers() as $publisher) {
             $publisher->publishBook();
        }

        // book publishing finishes
        $this->eventDispatcher->dispatch(Events::POST_PUBLISH, new Event());

        // execute the 'after_publish' scripts

//        $this->runScripts($this->app->edition('after_publish'));
        $this->runScripts((array) $this->parameterProvider->provideParameter('after_publish'));

        $this->symfonyStyle->success(
            'You can access the book in:' .
            realpath($this->app['publishing.dir.output'])
        );
    }

    /**
     * Run the given scripts before/after the book publication.
     *
     * @param array|string $scripts The list of scripts to be executed
     *
     * @throws \RuntimeException if any script execution produces an error.
     */
    private function runScripts($scripts): void
    {
        if ($scripts === null) {
            return;
        }

        if (is_array($scripts)) {
            foreach ($scripts as $script) {
                $this->runScripts($script);
            }

            return;
        }
        $process = new Process($scripts, $this->app['publishing.dir.book']);
        $process->run();

        if ($process->isSuccessful()) {
            $this->symfonyStyle->success($process->getOutput());
        } else {
            throw new RuntimeException(sprintf(
                'While executing scripts: %s an error happened: %s',
                $scripts . PHP_EOL . PHP_EOL,
                $process->getErrorOutput() . PHP_EOL
            ));
        }
    }
}
