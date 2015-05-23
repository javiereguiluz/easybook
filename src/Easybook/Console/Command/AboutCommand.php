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

class AboutCommand extends BaseCommand
{
    private $appVersion;

    public function __construct($appVersion)
    {
        parent::__construct();

        $this->appVersion = $appVersion;
    }

    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Displays the easybook usage help')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandHelp = <<<COMMAND_HELP

 easybook (%s)
 %s

 The easiest and fastest tool to generate technical documentation, books,
 manuals and websites.

 To create a new book, use the <info>new</> command:

   <comment>%s new "The Origin of Species"</comment>

 To publish a book, use the <info>publish</info> command:

   <comment>%3\$s publish the-origin-of-species print</comment>

 To display the installed version, use the <info>version</info> command:

   <comment>%3\$s version</comment>

 Checkout the full documentation for each command with the <info>help</info> command:

   <comment>%3\$s help new</comment>

COMMAND_HELP;

        $output->writeln(sprintf($commandHelp,
            $this->appVersion,
            str_repeat('=', 11 + strlen($this->appVersion)),
            $this->getExecutedCommand()
        ));
    }

    /**
     * Returns the executed command.
     *
     * @return string
     */
    private function getExecutedCommand()
    {
        $pathDirs = explode(PATH_SEPARATOR, $_SERVER['PATH']);
        $executedCommand = $_SERVER['PHP_SELF'];
        $executedCommandDir = dirname($executedCommand);

        if (in_array($executedCommandDir, $pathDirs)) {
            $executedCommand = basename($executedCommand);
        }

        return $executedCommand;
    }
}
