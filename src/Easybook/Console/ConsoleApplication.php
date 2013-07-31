<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Console;

use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Easybook\Console\Command\BookNewCommand;
use Easybook\Console\Command\BookPublishCommand;
use Easybook\Console\Command\BookCustomizeCommand;
use Easybook\Console\Command\EasybookVersionCommand;
use Easybook\Console\Command\EasybookBenchmarkCommand;
use Easybook\DependencyInjection\Application;

class ConsoleApplication extends SymfonyConsoleApplication
{
    private $app;

    public function getApp()
    {
        return $this->app;
    }

    public function __construct(Application $app)
    {
        $this->app = $app;

        parent::__construct('easybook', $this->app->getVersion());

        $this->add(new BookNewCommand());
        $this->add(new BookPublishCommand());
        $this->add(new BookCustomizeCommand());
        $this->add(new EasybookVersionCommand());
        $this->add(new EasybookBenchmarkCommand());
    }

    public function getHelp()
    {
        $help = array(
            $this->app['app.signature'],
            '<info>easybook</info> is the <comment>easiest</comment> and <comment>fastest</comment> tool to generate',
            'technical documentation, books, manuals and websites.'
        );

        return implode("\n", $help);
    }
}
