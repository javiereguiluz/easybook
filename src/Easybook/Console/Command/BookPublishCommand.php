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

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class BookPublishCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument(
                    'slug', InputArgument::REQUIRED, "Book slug (no spaces allowed)"
                ),
                new InputArgument(
                    'edition', InputArgument::REQUIRED, "Edition to be published"
                ),
                new InputOption(
                    'dir', '', InputOption::VALUE_OPTIONAL, "Absolute path of the book base directory. If not given, \nthe book must be in <comment>{easybook}/doc/{book-slug}</comment>"
                ),
            ))
            ->setName('publish')
            ->setDescription('Publishes an edition of a book')
            ->setHelp('The <info>publish</info> command helps you to publish an edition of a book.');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $slug    = $input->getArgument('slug');
        $edition = $input->getArgument('edition');

        $dir  = $input->getOption('dir') ?: $this->app['app.dir.doc'];
        $input->setOption('dir', $dir);

        $output->writeln($this->app['app.signature']);
        
        if ('' == $slug || '' == $edition) {
            $output->writeln(" Welcome to the <info>easybook</info> interactive book publisher");
        }

        $dialog = new DialogHelper();

        // Ask for the 'slug' if it doesn't exist
        $slugExists = file_exists($dir.'/'.$slug);
        while ('' == $slug || !$slugExists) {
            $slug = $dialog->ask($output, "\n Please, type the <info>slug</info> of the book (e.g. <comment>the-origin-of-species</comment>):\n > ");

            $slugExists = file_exists($dir.'/'.$slug);
            if ('' == $slug || !$slugExists) {
                $output->writeln(array(
                    "",
                    " <bg=red;fg=white> ERROR </> The <info>$slug</info> directory doesn't exist in:",
                    " <comment>".realpath($dir)."/</comment>",
                ));
            }
        }
        $input->setArgument('slug', $slug);
        
        $bookConfig = Yaml::parse($dir.'/'.$slug.'/config.yml');
        $this->app->set('book', $bookConfig['book']);
        
        // Ask for the 'edition' if it doesn't exist
        $editionExists = array_key_exists($edition, $this->app->book('editions'));
        while (!$editionExists) {
            // TODO: show the configured editions of the book
            $edition = $dialog->ask($output, "\n Please, type the name of the <info>edition</info> to be published:\n > ");

            $editionExists = array_key_exists($edition, $this->app->book('editions'));
            if (!$editionExists) {
                $output->writeln(array(
                "",
                " <bg=red;fg=white> ERROR </> The <info>$edition</info> edition isn't defined for <comment>".$this->app->book('title')."</comment> book",
                ));
            }
        }
        $input->setArgument('edition', $edition);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slug    = $input->getArgument('slug');
        $edition = $input->getArgument('edition');
        $dir     = $input->getOption('dir');
        
        // Add some useful values to the loaded configuration
        $bookdir = $dir.'/'.$slug;
        $this->app->set('publishing.dir.book',      $bookdir);
        $this->app->set('publishing.dir.contents',  $bookdir.'/Contents');
        $this->app->set('publishing.dir.plugins',   $bookdir.'/Resources/Plugins');
        $this->app->set('publishing.dir.templates', $bookdir.'/Resources/Templates');
        $this->app->set('publishing.book.slug',     $slug);
        $this->app->set('publishing.edition',       $edition);
        $this->app->set('publishing.dir.app_theme', $this->app['app.dir.theme_'.$this->app->edition('format')]);

        $this->registerPlugins();

        $this->app->dispatch(Events::PRE_PUBLISH, new BaseEvent($this->app));
        
        // Check that the given book already exists
        if (!file_exists($bookdir = $this->app['publishing.dir.book'])) {
           throw new \RuntimeException(sprintf(
               'The given book ("%s") doesn\'t exist in "%s" directory', $slug, realpath($bookdir)
           ));
        }
        
        // Show publishing message
        $output->writeln(sprintf(
            "\n Publishing '<comment>%s</comment>' edition of <info>%s</info> book...\n",
            $this->app['publishing.edition'],
            $this->app->book('title')
        ));
        
        // Check that the book has defined the given edition
        if (!array_key_exists($edition, $this->app->book('editions'))) {
           throw new \RuntimeException(sprintf(
               'There is no "%s" edition in "%s" configuration file',
               $edition,
               realpath($bookdir.'/config.yml')
           ));
        }

        // If the edition extends another one, check that they all exist and prepare
        if (null != $parent = $this->app->edition('extends')) {
            $this->app->extendEdition($parent);
        }

        // 1-line magic!
        $this->app->get('publisher')->publishBook();

        $this->app->dispatch(Events::POST_PUBLISH, new BaseEvent($this->app));

        $output->writeln(array(
            ' <bg=green;fg=black> SUCCESS </> You can access the book in the following directory:',
            ' <comment>'.realpath($this->app['publishing.dir.output']).'</comment>',
        ));
        
        $output->writeln(sprintf(
            "\n The publishing process took <info>%.1f seconds</info>\n",
            $this->app['app.timer.finish'] - $this->app['app.timer.start']
        ));
    }
}