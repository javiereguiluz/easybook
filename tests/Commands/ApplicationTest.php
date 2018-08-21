<?php declare(strict_types=1);

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = $this->container->get(Application::class);
        $this->application->setAutoExit(false);
    }

    public function testListCommand(): void
    {
        $tester = new ApplicationTester($this->console);
        $tester->run(['command' => 'list'], ['decorated' => false]);

        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/list_command_output.txt',
            $tester->getDisplay(),
            'Test the output of the "list" command.'
        );
    }

    public function testVersionCommand(): void
    {
        $tester = new ApplicationTester($this->console);
        $tester->run(['command' => 'version']);

        $this->assertContains(
            sprintf('easybook installed version: %s', $this->container->getParameter('version')),
            $tester->getDisplay(),
            'The "version" command shows the version of the application.'
        );
    }

    public function testApplicationSignature(): void
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
