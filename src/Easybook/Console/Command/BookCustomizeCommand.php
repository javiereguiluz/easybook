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
use Easybook\Util\Validator;

class BookCustomizeCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('customize')
            ->setDescription('Eases the customization of the book design')
            ->setDefinition(array(
                new InputArgument(
                    'slug', InputArgument::REQUIRED, "Book slug (no spaces allowed)"
                ),
                new InputArgument(
                    'edition', InputArgument::REQUIRED, "The name of the edition to be customized"
                ),
                new InputOption(
                    'dir', '', InputOption::VALUE_OPTIONAL, "Path of the documentation directory"
                )
            ))
            ->setHelp(file_get_contents(__DIR__.'/Resources/BookCustomizeCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slug    = $input->getArgument('slug');
        $edition = $input->getArgument('edition');
        $dir     = $input->getOption('dir') ?: $this->app['app.dir.doc'];

        $dialog  = $this->getHelperSet()->get('dialog');

        $this->app['console.input']  = $input;
        $this->app['console.output'] = $output;
        $this->app['console.dialog'] = $dialog;

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
        $this->app->loadBookConfiguration();

        // all checks passed, the book can now be customized
        $customizationDir = $this->app['publishing.dir.templates'].'/'.$edition;
        $this->prepareCustomizationDir($customizationDir);

        $customizationCssPath = $customizationDir.'/style.css';
        $this->prepareCustomizationCssFile($customizationCssPath);

        $output->writeln(array(
            " <bg=green;fg=black> OK </> You can now customize the book design with the following stylesheet:\n",
            " <info>$customizationCssPath</info> \n\n",
            sprintf(
                " <bg=green;fg=black> TIP </> If you want to customize every edition of <info>'%s'</info> format,\n"
                ." rename:\n     %s/<info>%s</info>\n"
                ." to:\n    %s/<info>%s</info>\n",
                $this->app->edition('format'),
                $this->app['publishing.dir.templates'], $edition,
                $this->app['publishing.dir.templates'], $this->app->edition('format')
            ),
        ));
    }

    private function prepareCustomizationDir($dir)
    {
        if (!file_exists($dir)) {
            $this->app['filesystem']->mkdir($dir);
        }
    }

    private function prepareCustomizationCssFile($file)
    {
        $customizationSkeleton = sprintf('%s/Customization/%s/style.css',
            $this->app['app.dir.skeletons'], $this->app->edition('format')
        );

        if (!file_exists($file)) {
            $this->app['filesystem']->copy($customizationSkeleton, $file);
        } else {
            throw new \RuntimeException(sprintf(
                "ERROR: The '%s' edition already contains a custom CSS stylesheet.\n"
                    ." You can find it at the following file:\n\n"
                    ." %s",
                $this->app['publishing.edition'], $file
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
            ' Welcome to the <comment>easybook</comment> interactive book customizer',
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
                " Please, type the name of the <info>edition</info> to be customized (e.g. <comment>web</comment>)\n",
                " > "
            ),
            function ($edition) {
                return Validator::validateEditionSlug($edition);
            }
        );
        $input->setArgument('edition', $edition);
    }
}
