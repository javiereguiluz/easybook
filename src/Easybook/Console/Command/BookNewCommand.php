<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Events\AbstractEvent;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Generator\BookGenerator;
use Easybook\Util\Slugger;
use Easybook\Util\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    /**
     * @var Slugger
     */
    private $slugger;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var string
     */
    private $bookTitle;

    public function __construct(
        BookGenerator $bookGenerator,
        SymfonyStyle $symfonyStyle,
        Slugger $slugger,
        EventDispatcherInterface $eventDispatcher,
        string $bookTitle
    )
    {
        parent::__construct();
        $this->bookGenerator = $bookGenerator;
        $this->symfonyStyle = $symfonyStyle;
        $this->slugger = $slugger;
        $this->eventDispatcher = $eventDispatcher;
        $this->bookTitle = $bookTitle;
    }

    protected function configure(): void
    {
        $this->setName('new');
        $this->setDescription('Creates a new empty book');
        $this->addOption(Option::DIR, null, InputOption::VALUE_OPTIONAL, 'Path of the documentation directory');
        $this->setHelp(file_get_contents(__DIR__ . '/Resources/BookNewCommandHelp.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        // yaml vs CLI?

        $dir = Validator::validateDirExistsAndWritable($input->getOption('dir') ?: $this->app['app.dir.doc']);


        $bookSlug = $this->slugger->slugify($this->bookTitle);

        $this->eventDispatcher->dispatch(Events::PRE_NEW, new Event());

        $this->bookGenerator->setSkeletonDirectory($this->app['app.dir.skeletons'] . '/Book');
        $this->bookGenerator->setBookDirectory($dir . '/' . $bookSlug);
        $this->bookGenerator->setConfiguration([
            'generator' => [
                'name' => $this->getName(),
                'version' => $this->app->getVersion(),
            ],
            'title' => $this->bookTitle,
        ]);

        $this->bookGenerator->generate();

        $this->eventDispatcher->dispatch(Events::POST_NEW, new Event());

        $this->symfonyStyle->success(
            'You can start writing your book in the following directory: ' . realpath(
                $this->bookGenerator->getBookDirectory()
            )
        );
    }
}
