<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Commands;

use Easybook\DependencyInjection\Application;
use Easybook\Console\ConsoleApplication;
use Symfony\Component\Console\Tester\ApplicationTester;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    private $app;
    private $console;

    public function setUp()
    {
        $this->app = new Application();
        $this->console = new ConsoleApplication($this->app);
        $this->console->setAutoExit(false);
    }

    public function testCommandList()
    {
        $tester = new ApplicationTester($this->console);
        $tester->run(array(), array('decorated' => false));

        $this->assertStringEqualsFile(
            __DIR__.'/fixtures/application_output.txt',
            $tester->getDisplay(),
            'Executing the application without arguments shows the commands list'
        );
    }

    public function testVersion()
    {
        $tester = new ApplicationTester($this->console);
        $tester->run(array('command' => 'version'));

        $this->assertRegExp(
            sprintf('/easybook installed version: %s/', preg_quote($this->app->getVersion())),
            $tester->getDisplay(),
            'The "version" command shows the version of the application'
        );
    }

    public function testSignature()
    {
        $tester = new ApplicationTester($this->console);
        $tester->run(array(), array('decorated' => false));

        $this->assertContains(
            $this->app['app.signature'],
            $tester->getDisplay(),
            'The signature of the application is shown'
        );
    }
}
