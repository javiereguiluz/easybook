<?php declare(strict_types=1);

namespace Easybook\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AboutCommand extends Command
{
    /**
     * @var string
     */
    private $signature;

    public function __construct(string $signature)
    {
        parent::__construct();

        $this->signature = $signature;
    }

    protected function configure(): void
    {
        $this->setName('about');
        $this->setDescription('Displays the easybook usage help');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln(sprintf(file_get_contents(__DIR__ . '/Resources/AboutCommandHelp.txt'), $this->signature));
    }
}
