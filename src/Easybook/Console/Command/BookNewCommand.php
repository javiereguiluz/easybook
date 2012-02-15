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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Easybook\DependencyInjection\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class BookNewCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument(
                    'title', InputArgument::REQUIRED, "Book title (wrap it with quotes)"
                ),
                new InputOption(
                    'dir', '', InputOption::VALUE_OPTIONAL, "Absolute path of the book parent directory.If not given, \nthe book will be generated in <comment>{easybook}/doc/{book-slug}</comment>"
                ),
            ))  
            ->setName('new')
            ->setDescription('Creates a new empty book')
            ->setHelp("The <info>new</info> command generates the file and directory structure required by books.\n");
    }
    
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $title = trim($input->getArgument('title'));

        $dialog = new DialogHelper();

        if ('' == $title) {
            $output->writeln(array(
                $this->app['app.signature'],
                ' Welcome to the <info>easybook</info> interactive book generator'
            ));

            // Ask for the 'title' if it doesn't exist
            while ('' == $title) {
                $title = $dialog->ask($output, "\n Please, type the <info>title</info> of the book (e.g. <comment>'The Origin of Species'</comment>)\n > ");
            }
            $input->setArgument('title', $title);
        }
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $title   = $input->getArgument('title');
        $basedir = trim($input->getOption('dir')) ?: $this->app['app.dir.doc'];

        // TODO: extensibility: user should be allowed to define the slug
        $slug = $this->app->get('slugger')->slugify($title);
        $docdir = $basedir.'/'.$slug;

        $this->registerPlugins();
        $this->app->dispatch(Events::PRE_NEW, new BaseEvent($this->app));

        // check if `docdir` directory is available
        // If not, create a unique directory name appending a numeric suffix
        $i = 1;
        while (file_exists($docdir)) {
            $docdir = $basedir.'/'.$slug.'-'.$i++;
        }

        // create the skeleton of the new book
        // don't use mirror() method because git repository deletes empty directories
        $skeletonDir = $this->app['app.dir.skeletons'].'/Book';
        $this->app->get('filesystem')->mkdir($docdir.'/Contents');
        $this->app->get('filesystem')->copy(
            $skeletonDir.'/Contents/chapter1.md',
            $docdir.'/Contents/chapter1.md'
        );
        $this->app->get('filesystem')->copy(
            $skeletonDir.'/Contents/chapter2.md',
            $docdir.'/Contents/chapter2.md'
        );
        $this->app->get('filesystem')->mkdir($docdir.'/Contents/images');
        $this->app->get('filesystem')->mkdir($docdir.'/Output');
        $this->app->renderFile($skeletonDir, 'config.yml.twig', $docdir.'/config.yml', array(
            'generator' => array(
                'name'    => $this->app['app.name'],
                'version' => $this->app['app.version']
            ),
            'title' => $title,
        ));

        $this->app->dispatch(Events::POST_NEW, new BaseEvent($this->app));

        $output->writeln(array(
            '',
            ' <bg=green;fg=black> SUCCESS </> You can start writing your book in the following directory:',
            ' <comment>'.realpath($docdir).'</comment>',
            ''
        ));
    }
}