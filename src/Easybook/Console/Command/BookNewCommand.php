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
use Easybook\Console\Command\Validators;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class BookNewCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Creates a new empty book')
            ->setDefinition(array(
                new InputArgument(
                    'title', InputArgument::REQUIRED, "Book title"
                ),
                new InputOption(
                    'dir', '', InputOption::VALUE_OPTIONAL, "Path of the documentation directory"
                ),
            ))
            ->setHelp(<<<EOT
The <info>new</info> command generates the file and directory structure
required by <comment>easybook</comment> books.

If you don't include any parameter, the command will guide you through
an interactive generator. You can bypass the interactive generator typing
the title of the book after the <info>new</info> command (enclose the title with quotes):

<info>$ ./book new "The Origin of Species"</info>

By default, <comment>easybook</comment> uses its <info>doc/</info> directory to save the book contents.
If you want to save them in any other directory, use the <info>--dir</info> option:

<info>$ ./book new --dir=../any/other/directory</info>

The value of <info>--dir</info> option is considered as the parent directory of
the book directory. In the previous example, the book will be created in the 
following directory:

any/
  other/
    directory/
      {book-slug}/
          config.yml
          Contents/
              chapter1.md
              chapter2.md

You can type the title and use the <info>--dir</info> option simultaneously:

<info>$ ./book new "The Origin of Species" --dir=../any/other/directory</info>

EOT
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $title = Validators::validateNonEmptyString(
            'title', $input->getArgument('title')
        );
        
        $dir = Validators::validateDir(
            $input->getOption('dir') ?: $this->app['app.dir.doc']
        );
        
        // TODO: extensibility: user should be allowed to define the slug
        $slug = $this->app->get('slugger')->slugify($title);
        $bookDir = $dir.'/'.$slug;

        $this->registerPlugins();
        $this->app->dispatch(Events::PRE_NEW, new BaseEvent($this->app));

        // check if `$bookDir` directory is available
        // if not, create a unique directory name appending a numeric suffix
        $i = 1;
        while (file_exists($bookDir)) {
            $bookDir = $dir.'/'.$slug.'-'.$i++;
        }

        // create the skeleton of the new book
        // don't use mirror() method because git deletes empty directories
        $skeletonDir = $this->app['app.dir.skeletons'].'/Book';
        $this->app->get('filesystem')->mkdir($bookDir.'/Contents');
        $this->app->get('filesystem')->copy(
            $skeletonDir.'/Contents/chapter1.md',
            $bookDir.'/Contents/chapter1.md'
        );
        $this->app->get('filesystem')->copy(
            $skeletonDir.'/Contents/chapter2.md',
            $bookDir.'/Contents/chapter2.md'
        );
        $this->app->get('filesystem')->mkdir($bookDir.'/Contents/images');
        $this->app->get('filesystem')->mkdir($bookDir.'/Output');
        $this->app->renderFile($skeletonDir, 'config.yml.twig', $bookDir.'/config.yml', array(
            'generator' => array(
                'name'    => $this->app['app.name'],
                'version' => $this->app['app.version']
            ),
            'title' => $title,
        ));

        $this->app->dispatch(Events::POST_NEW, new BaseEvent($this->app));

        $output->writeln(array(
            '',
            ' <bg=green;fg=black> OK </> You can start writing your book in the following directory:',
            ' <comment>'.realpath($bookDir).'</comment>',
            ''
        ));
    }
    
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->app['app.signature']);
        
        $title = $input->getArgument('title');
        if (null != $title && '' != $title) {
            return;
        }
        
        $output->writeln(array(
            '',
            ' Welcome to the <comment>easybook</comment> interactive book generator',
            ''
        ));
        
        $dialog = $this->getHelperSet()->get('dialog');
        
        // check `title` argument
        $title = $input->getArgument('title') ?: $dialog->askAndValidate(
            $output,
            "\n Please, type the <info>title</info> of the book"
            ." (e.g. <comment>The Origin of Species</comment>)"
            ."\n > ",
            function ($title) {
                return Validators::validateNonEmptyString('title', $title);
            }
        );
        $input->setArgument('title', $title);
    }
}