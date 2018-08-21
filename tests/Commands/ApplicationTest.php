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

use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

final class ApplicationTest extends AbstractContainerAwareTestCase
{
    private $app;
    private $console;

    /**
     * @var Application
     */
    private $application;

    public function setUp()
    {
        parent::setUp();

        $this->application = $this->container->get(Application::class);
        $this->application->setAutoExit(false);
    }

    public function testListCommand()
    {
        $tester = new ApplicationTester($this->console);
        $tester->run(['command' => 'list'], ['decorated' => false]);

        $this->assertStringEqualsFile(
            __DIR__.'/fixtures/list_command_output.txt',
            $tester->getDisplay(),
            'Test the output of the "list" command.'
        );
    }

    public function testVersionCommand()
    {
        $tester = new ApplicationTester($this->console);
        $tester->run(['command' => 'version']);

        $this->assertContains(
            sprintf('easybook installed version: %s', $this->container->getParameter('version')),
            $tester->getDisplay(),
            'The "version" command shows the version of the application.'
        );
    }

    public function testApplicationSignature()
    {
        $tester = new ApplicationTester($this->console);
        $tester->run([], ['decorated' => false]);

        $this->assertContains(
            $this->app['app.signature'],
            $tester->getDisplay(),
            'The default command displays the signature of the application.'
        );
    }
}
