<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Util\Validator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

final class BookCustomizeCommand extends Command
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this->setName('customize');
        $this->setDescription('Eases the customization of the book design');
        $this->addArgument(Option::SLUG, InputArgument::REQUIRED, 'Book slug (no spaces allowed)');
        $this->addArgument(Option::EDITION, InputArgument::REQUIRED, 'The name of the edition to be customized');
        $this->addOption('dir', '', InputOption::VALUE_OPTIONAL, 'Path of the documentation directory');
        $this->setHelp(file_get_contents(__DIR__ . '/Resources/BookCustomizeCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $slug = $input->getArgument('slug');
        $edition = $input->getArgument('edition');
        $dir = $input->getOption('dir') ?: $this->app['app.dir.doc'];

//        $dialog = $this->getHelperSet()->get('dialog');

//        $this->app['console.dialog'] = $dialog;

        // validate book dir and add some useful values to the app configuration
        $bookDir = $this->app['validator']->validateBookDir($slug, $dir);

        $this->app['publishing.dir.book'] = $bookDir;
        $this->app['publishing.dir.contents'] = $bookDir . '/Contents';
        $this->app['publishing.dir.resources'] = $bookDir . '/Resources';
        $this->app['publishing.dir.plugins'] = $bookDir . '/Resources/Plugins';
        $this->app['publishing.dir.templates'] = $bookDir . '/Resources/Templates';
        $this->app['publishing.book.slug'] = $slug;
        $this->app['publishing.edition'] = $edition;

        // load book configuration
        $this->app->loadBookConfiguration();

        // all checks passed, the book can now be customized
        $customizationDir = $this->app['publishing.dir.templates'] . '/' . $edition;
        $this->prepareCustomizationDir($customizationDir);

        $customizationCssPath = $customizationDir . '/style.css';
        $this->prepareCustomizationCssFile($customizationCssPath);

        $output->writeln([
            " <bg=green;fg=black> OK </> You can now customize the book design with the following stylesheet:\n",
            " <info>${customizationCssPath}</info> \n\n",
            sprintf(
                " <bg=green;fg=black> TIP </> If you want to customize every edition of <info>'%s'</info> format,\n"
                . " rename:\n     %s/<info>%s</info>\n"
                . " to:\n    %s/<info>%s</info>\n",
                $this->app->edition('format'),
                $this->app['publishing.dir.templates'],
                $edition,
                $this->app['publishing.dir.templates'],
                $this->app->edition('format')
            ),
        ]);
    }

    private function prepareCustomizationDir($dir): void
    {
        $this->filesystem->mkdir($dir);
    }

    private function prepareCustomizationCssFile($file): void
    {
        $customizationSkeleton = sprintf(
            '%s/Customization/%s/style.css',
            $this->app['app.dir.skeletons'],
            $this->app->edition('format')
        );

        if (! file_exists($file)) {
            $this->filesystem->copy($customizationSkeleton, $file);
        } else {
            throw new RuntimeException(sprintf(
                "The '%s' edition already contains a custom CSS stylesheet.\n"
                    . " You can find it at the following file:\n\n"
                    . ' %s',
                $this->app['publishing.edition'],
                $file
            ));
        }
    }
}
