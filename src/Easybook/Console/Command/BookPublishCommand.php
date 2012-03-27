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
use Easybook\Console\Command\Validators;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class BookPublishCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('publish')
            ->setDescription('Publishes an edition of a book')
            ->setDefinition(array(
                new InputArgument(
                    'slug', InputArgument::REQUIRED, "Book slug (no spaces allowed)"
                ),
                new InputArgument(
                    'edition', InputArgument::REQUIRED, "Edition to be published"
                ),
                new InputOption(
                    'dir', '', InputOption::VALUE_OPTIONAL, "Path of the documentation directory"
                ),
            ))
            ->setHelp(<<<EOT
The <info>publish</info> command publishes an edition of a book.

If you don't include any parameter, the command will guide you through an
interactive publisher. You can bypass the interactive publisher typing the
slug of the book and the name of the edition after the <info>publish</info> command:

<info>$ ./book publish the-origin-of-species print</info>

By default, <comment>easybook</comment> looks for the book contents in its <info>doc/</info> directory.
If your book is in another directory, use the <info>--dir</info> option:

<info>$ ./book publish the-origin-of-species print --dir=../any/other/directory</info>

The value of <info>--dir</info> option is considered as the parent directory of
the book directory. In the previous example, the book must be in the following
directory:

any/
  other/
    directory/
      the-origin-of-species/
          config.yml
          Contents/
              chapter1.md
              chapter2.md

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog  = $this->getHelperSet()->get('dialog');
        
        $slug    = $input->getArgument('slug');
        $edition = $input->getArgument('edition');
        $dir     = $input->getOption('dir') ?: $this->app['app.dir.doc'];
        
        $bookDir = $dir.'/'.$slug;
        
        // check that the given book already exists or ask for another slug
        $attemps = 6;
        while (!file_exists($bookDir) && $attemps--) {
            if (!$attemps) {
                throw new \RuntimeException(sprintf(
                    " ERROR: Too many failed attempts. Check that your book is in\n"
                    ." '%s/' directory",
                    realpath($dir)
                ));
            }
            
            $output->writeln(array(
                "",
                " <bg=red;fg=white> ERROR </> The given <info>$slug</info> slug doesn't match any book in",
                " <comment>".realpath($dir)."/</comment> directory"
            ));
            
            $slug = $dialog->ask($output, array(
                "\n Please, type the <info>slug</info> of the book (e.g. <comment>the-origin-of-species</comment>)\n"
                ." > "
            ));
            
            $bookDir = $dir.'/'.$slug;
        }
        
        // add some useful values to the app configuration
        $this->app->set('publishing.dir.book',      $bookDir);
        $this->app->set('publishing.dir.contents',  $bookDir.'/Contents');
        $this->app->set('publishing.dir.resources', $bookDir.'/Resources');
        $this->app->set('publishing.dir.plugins',   $bookDir.'/Resources/Plugins');
        $this->app->set('publishing.dir.templates', $bookDir.'/Resources/Templates');
        $this->app->set('publishing.book.slug',     $slug);
        
        // check that the book has a configuration file
        $bookConfigFile = $bookDir.'/config.yml';
        if (!file_exists($bookConfigFile)) {
            throw new \RuntimeException(sprintf(
                "There is no 'config.yml' configuration file for '%s' book \n\n"
                ."Try to create the book again with the 'new' command or create \n"
                ."'%s' file by hand",
                $slug, realpath($bookDir).'/config.yml'
            ));
        }
        
        // check that the config file is correct (trivial check for now)
        $bookConfig = Yaml::parse($bookConfigFile);
        if (!array_key_exists('book', $bookConfig)) {
            throw new \RuntimeException(sprintf(
                "Malformed 'config.yml' configuration file for '%s' book \n\n"
                ."Open '%s' file\n"
                ."and add at least the 'book' root option ",
                $slug, realpath($bookDir).'/config.yml'
            ));
        }
        $this->app->set('book', $bookConfig['book']);
        
        // check that the book has defined the given edition or ask for another edition
        $attemps = 6;
        while (!array_key_exists($edition, $this->app->book('editions')) && $attemps--) {
            if (!$attemps) {
                throw new \RuntimeException(sprintf(
                    " ERROR: Too many failed attempts. Check that your book has a\n"
                    ." '%s' edition defined in the following configuration file:\n"
                    ." '%s'",
                    $edition, realpath($bookConfigFile)
                ));
            }
        
            $output->writeln(array(
                "",
                " <bg=red;fg=white> ERROR </> The <info>$edition</info> edition isn't defined for "
                ."<comment>".$this->app->book('title')."</comment> book",
                "",
                " Check that <comment>".realpath($bookDir.'/config.yml')."</comment> file",
                " defines a <info>$edition</info> edition under the <info>editions</info> option."
            ));
            
            $edition = $dialog->ask($output, array(
                "\n Please, type the name of the <info>edition</info> to be published:\n"
                ." > "
            ));
        }
        
        // add some useful values to the app configuration
        $this->app->set('publishing.edition', $edition);
        $this->app->loadEditionConfig();
        
        // all checks passed, the book can be published
        
        // register easybook and custom book plugins
        $this->registerPlugins();
        
        // book publishing starts
        $this->app->dispatch(Events::PRE_PUBLISH, new BaseEvent($this->app));
        $output->writeln(array(
            '',
            sprintf(
                " Publishing <comment>%s</comment> edition of <info>%s</info> book...",
                $edition, $this->app->book('title')
            ),
            ''
        ));
        
        // 1-line magic publication!
        $this->app->get('publisher')->publishBook();
        
        // book publishing finishes
        $this->app->dispatch(Events::POST_PUBLISH, new BaseEvent($this->app));
        
        $output->writeln(array(
            ' <bg=green;fg=black> OK </> You can access the book in the following directory:',
            ' <comment>'.realpath($this->app['publishing.dir.output']).'</comment>',
            '',
            sprintf(
                " The publishing process took <info>%.1f seconds</info>\n",
                $this->app['app.timer.finish'] - $this->app['app.timer.start']
            )
        ));
    }
    
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->app['app.signature']);
        
        $slug    = $input->getArgument('slug');
        $edition = $input->getArgument('edition');
        if (null != $slug && '' != $slug && null != $edition && '' != $edition) {
            return;
        }
        
        $output->writeln(array(
            '',
            ' Welcome to the <comment>easybook</comment> interactive book publisher',
            ''
        ));
        
        $dialog = $this->getHelperSet()->get('dialog');
        
        // check `slug` argument
        $slug = $input->getArgument('slug') ?: $dialog->askAndValidate(
            $output,
            array(
                " Please, type the <info>slug</info> of the book (e.g. <comment>the-origin-of-species</comment>)\n",
                " > "
            ),
            function ($slug) {
                return Validators::validateBookSlug($slug);
            }
        );
        $input->setArgument('slug', $slug);
        
        // check `edition` argument
        $edition = $input->getArgument('edition') ?: $dialog->askAndValidate(
            $output,
            array(
                " Please, type the name of the <info>edition</info> to be published (e.g. <comment>web</comment>)\n",
                " > "
            ),
            function ($edition) {
                return Validators::validateNonEmptyString('edition', $edition);
            }
        );
        $input->setArgument('edition', $edition);
    }
}