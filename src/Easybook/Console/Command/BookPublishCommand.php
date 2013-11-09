<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;
use Easybook\Util\Validator;

class BookPublishCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('publish')
            ->setDescription('Publishes an edition of a book')
            ->setDefinition(array(
                new InputArgument(
                    'slug', InputArgument::REQUIRED, "Book slug (no spaces allowed, use dashes instead)"
                ),
                new InputArgument(
                    'edition', InputArgument::REQUIRED, "Edition to be published"
                ),
                new InputOption(
                    'dir', '', InputOption::VALUE_OPTIONAL, "Path of the documentation directory"
                ),
                new InputOption(
                    'configuration', '', InputOption::VALUE_OPTIONAL, "Additional book configuration options", ""
                ),
            ))
            ->setHelp(file_get_contents(__DIR__.'/Resources/BookPublishCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slug    = $input->getArgument('slug');
        $edition = $input->getArgument('edition');
        $dir     = $input->getOption('dir') ?: $this->app['app.dir.doc'];

        $this->app['console.input']  = $input;
        $this->app['console.output'] = $output;
        $this->app['console.dialog'] = $this->getHelperSet()->get('dialog');

        // validate book dir and add some useful values to the app configuration
        $bookDir = $this->app['validator']->validateBookDir($slug, $dir);

        $this->app['publishing.dir.book']      = $bookDir;
        $this->app['publishing.dir.contents']  = $bookDir.'/Contents';
        $this->app['publishing.dir.resources'] = $bookDir.'/Resources';
        $this->app['publishing.dir.plugins']   = $bookDir.'/Resources/Plugins';
        $this->app['publishing.dir.templates'] = $bookDir.'/Resources/Templates';
        $this->app['publishing.book.slug']     = $slug;
        $this->app['publishing.edition']       = $edition;

        // load book configuration
        $this->app->loadBookConfiguration($input->getOption('configuration'));

        // register easybook and custom book plugins
        $this->registerPlugins();

        // load the custom easybook parameters defined by the book
        $this->app->loadEasybookConfiguration();

        // execute the 'before_publish' scripts
        $this->runScripts($this->app->edition('before_publish'));

        // book publishing starts
        $this->app->dispatch(Events::PRE_PUBLISH, new BaseEvent($this->app));
        $output->writeln(sprintf(
            "\n Publishing <comment>%s</comment> edition of <info>%s</info> book...\n",
            $edition, $this->app->book('title')
        ));

        // 1-line magic publication!
        $this->app['publisher']->publishBook();

        // book publishing finishes
        $this->app->dispatch(Events::POST_PUBLISH, new BaseEvent($this->app));

        // execute the 'after_publish' scripts
        $this->runScripts($this->app->edition('after_publish'));

        $output->writeln(array(
            ' <bg=green;fg=black> OK </> You can access the book in the following directory:',
            ' <comment>'.realpath($this->app['publishing.dir.output']).'</comment>',
            '',
            sprintf(
                " The publishing process took <info>%s seconds</info>\n",
                number_format($this->app['app.timer.finish'] - $this->app['app.timer.start'], 1)
            )
        ));
    }

    /**
     * Run the given scripts before/after the book publication.
     *
     * @param  array|string $scripts The list of scripts to be executed
     *
     * @return void
     *
     * @throws \RuntimeException if any script execution produces an error.
     */
    private function runScripts($scripts)
    {
        if (null == $scripts) {
            return;
        }

        if (is_array($scripts)) {
            foreach ($scripts as $script) {
                $this->runScripts($script);
            }

            return;
        }
        $process = new Process(
            $this->app->renderString($scripts),
            $this->app['publishing.dir.book']
        );
        $process->run();

        if ($process->isSuccessful()) {
            echo $process->getOutput();
        } else {
            throw new \RuntimeException(sprintf(
                "There was an error executing the following script: \n"
                ."  %s\n\n"
                ."  %s\n",
                $scripts, $process->getErrorOutput()
            ));
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->app['app.signature']);

        $slug    = $input->getArgument('slug');
        $edition = $input->getArgument('edition');

        if (!empty($slug) && !empty($edition)) {
            return;
        }

        $output->writeln(array(
            '',
            ' Welcome to the <comment>easybook</comment> interactive book publisher',
            ''
        ));

        $dialog = $this->getHelperSet()->get('dialog');

        // check 'slug' argument
        $slug = $input->getArgument('slug') ?: $dialog->askAndValidate($output,
            array(
                " Please, type the <info>slug</info> of the book (e.g. <comment>the-origin-of-species</comment>)\n",
                " > "
            ),
            function ($slug) {
                return Validator::validateBookSlug($slug);
            }
        );
        $input->setArgument('slug', $slug);

        // check 'edition' argument
        $edition = $input->getArgument('edition') ?: $dialog->askAndValidate($output,
            array(
                " Please, type the name of the <info>edition</info> to be published (e.g. <comment>web</comment>)\n",
                " > "
            ),
            function ($edition) {
                return Validator::validateEditionSlug($edition);
            }
        );
        $input->setArgument('edition', $edition);
    }
}
