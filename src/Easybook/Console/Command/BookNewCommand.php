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
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;
use Easybook\Generator\BookGenerator;
use Easybook\Util\Validator;

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
            ->setHelp(file_get_contents(__DIR__.'/Resources/BookNewCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $title = Validator::validateNonEmptyString(
            'title', $input->getArgument('title')
        );

        $dir = Validator::validateDirExistsAndWritable(
            $input->getOption('dir') ?: $this->app['app.dir.doc']
        );

        $slug = $this->app->slugify($title);

        $this->registerPlugins();
        $this->app->dispatch(Events::PRE_NEW, new BaseEvent($this->app));

        $generator = new BookGenerator();
        $generator->setFilesystem($this->app['filesystem']);
        $generator->setSkeletonDirectory($this->app['app.dir.skeletons'].'/Book');
        $generator->setBookDirectory($dir.'/'.$slug);
        $generator->setConfiguration(array(
            'generator' => array(
                'name'    => $this->app['app.name'],
                'version' => $this->app->getVersion(),
            ),
            'title' => $title,
        ));
        $generator->generate();

        $this->app->dispatch(Events::POST_NEW, new BaseEvent($this->app));

        $output->writeln(array(
            '',
            ' <bg=green;fg=black> OK </> You can start writing your book in the following directory:',
            ' <comment>'.realpath($generator->getBookDirectory()).'</comment>',
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
                return Validator::validateNonEmptyString('title', $title);
            }
        );
        $input->setArgument('title', $title);
    }
}
