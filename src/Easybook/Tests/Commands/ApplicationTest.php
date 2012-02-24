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
    public function testCommandList()
    {
        $app = new Application();
        $console = new ConsoleApplication($app);
        $console->setAutoExit(false);

        $tester = new ApplicationTester($console);
        $tester->run(array());

        $this->assertStringEqualsFile(
            __DIR__.'/fixtures/application_output.txt',
            $tester->getDisplay(),
            'Executing the application without arguments shows the commands list'
        );
    }

    public function testVersion()
    {
        $app = new Application();
        $console = new ConsoleApplication($app);
        $console->setAutoExit(false);
        
        $tester = new ApplicationTester($console);
        $tester->run(array('command' => 'version'));

        $this->assertRegExp(
            sprintf('/%s/', preg_quote($app['app.version'])),
            $tester->getDisplay(),
            'The "version" command shows the version of the application'
        );
    }
}
