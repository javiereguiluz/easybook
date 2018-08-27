<?php declare(strict_types=1);

namespace Easybook\Tests\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Console\Command\NewCommand;
use Easybook\Console\Command\PublishCommand;
use Easybook\Tests\AbstractCustomConfigContainerAwareTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class FailScriptsTest extends AbstractCustomConfigContainerAwareTestCase
{
    /**
     * @var string
     */
    private $bookDirectory;

    /**
     * @var PublishCommand
     */
    private $bookPublishCommand;

    protected function setUp(): void
    {
        // generate a sample book before testing its publication
        $this->bookDirectory = sys_get_temp_dir() . '/_easybook_tests/' . uniqid();

        $newCommand = $this->container->get(NewCommand::class);
        (new CommandTester($newCommand))->execute([
            Option::BOOK_DIR => $this->bookDirectory,
        ]);

        $this->bookPublishCommand = $this->container->get(PublishCommand::class);
    }

    public function test(): void
    {
        $this->expectException(ProcessFailedException::class);

        $tester = new CommandTester($this->bookPublishCommand);
        $tester->execute([
            Option::BOOK_DIR => $this->bookDirectory,
        ]);
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/config/fail-scripts.yml';
    }
}
