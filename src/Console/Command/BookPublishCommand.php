<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Util\Validator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;

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

    public function __construct(EventDispatcherInterface $eventDispatcher, string $bookTitle, Validator $validator)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->bookTitle = $bookTitle;
        $this->validator = $validator;
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

        // load book configuration
        $this->app->loadBookConfiguration($input->getOption('configuration'));

        // load the custom easybook parameters defined by the book
        $this->app->loadEasybookConfiguration();

        // execute the 'before_publish' scripts
        $this->runScripts($this->app->edition('before_publish'));

        // book publishing starts
        $this->eventDispatcher->dispatch(Events::PRE_PUBLISH, new Event());

        $output->writeln(sprintf(
            "\n Publishing <comment>%s</comment> edition of <info>%s</info> book...\n",
            $edition,
            $this->bookTitle
        ));

        // 1-line magic publication!
        $this->app['publisher']->publishBook();

        // book publishing finishes
        $this->eventDispatcher->dispatch(Events::POST_PUBLISH, new Event());

        // execute the 'after_publish' scripts
        $this->runScripts($this->app->edition('after_publish'));

        $output->writeln([
            ' <bg=green;fg=black> OK </> You can access the book in the following directory:',
            ' <comment>' . realpath($this->app['publishing.dir.output']) . '</comment>',
        ]);
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
        $process = new Process($this->app->renderString($scripts), $this->app['publishing.dir.book']);
        $process->run();

        if ($process->isSuccessful()) {
            echo $process->getOutput();
        } else {
            throw new RuntimeException(sprintf(
                "There was an error executing the following script: \n"
                . "  %s\n\n"
                . "  %s\n",
                $scripts,
                $process->getErrorOutput()
            ));
        }
    }
}