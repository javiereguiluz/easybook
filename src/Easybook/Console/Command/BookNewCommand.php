<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Events\AbstractEvent;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Generator\BookGenerator;
use Easybook\Util\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class BookNewCommand extends Command
{
    /**
     * @var BookGenerator
     */
    private $bookGenerator;
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(BookGenerator $bookGenerator, SymfonyStyle $symfonyStyle)
    {
        parent::__construct();
        $this->bookGenerator = $bookGenerator;
        $this->symfonyStyle = $symfonyStyle;
    }

    protected function configure(): void
    {
        $this->setName('new');
        $this->setDescription('Creates a new empty book');
        $this->addArgument(Option::TITLE, InputArgument::REQUIRED, 'Book title');
        $this->addOption(Option::DIR, '', InputOption::VALUE_OPTIONAL, 'Path of the documentation directory');
        $this->setHelp(file_get_contents(__DIR__ . '/Resources/BookNewCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $dir = Validator::validateDirExistsAndWritable($input->getOption('dir') ?: $this->app['app.dir.doc']);

        $slug = $this->app->slugify($input->getArgument('title'));

        $this->app->dispatch(Events::PRE_NEW, new AbstractEvent($this->app));

        $this->bookGenerator->setSkeletonDirectory($this->app['app.dir.skeletons'] . '/Book');
        $this->bookGenerator->setBookDirectory($dir . '/' . $slug);
        $this->bookGenerator->setConfiguration([
            'generator' => [
                'name' => $this->app['app.name'],
                'version' => $this->app->getVersion(),
            ],
            'title' => $title,
        ]);
        $this->bookGenerator->generate();

        $this->app->dispatch(Events::POST_NEW, new AbstractEvent($this->app));

        $this->symfonyStyle->success(
            'You can start writing your book in the following directory:' .
            ' <comment>' . realpath($this->bookGenerator->getBookDirectory()) . '</comment>'
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $title = $input->getArgument('title');
        if ($title !== null && $title !== '') {
            return;
        }

        $this->symfonyStyle->writeln('Welcome to the <comment>easybook</comment> interactive book generator');
    }
}
