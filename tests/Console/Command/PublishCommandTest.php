<?php declare(strict_types=1);

namespace Easybook\Tests\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Console\Command\NewCommand;
use Easybook\Console\Command\PublishCommand;
use Easybook\Tests\AbstractCustomConfigContainerAwareTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class PublishCommandTest extends AbstractCustomConfigContainerAwareTestCase
{
    /**
     * @var PublishCommand
     */
    private $publishCommand;

    /**
     * @var string
     */
    private $tmpDir;

    protected function setUp(): void
    {
        // generate a sample book before testing its publication
        $this->tmpDir = sys_get_temp_dir() . '/_easybook_tests/' . uniqid();

        $newCommand = $this->container->get(NewCommand::class);
        (new CommandTester($newCommand))->execute([
            Option::BOOK_DIR => $this->tmpDir,
        ]);

        $this->publishCommand = $this->container->get(PublishCommand::class);
    }

    public function test(): void
    {
        $tester = new CommandTester($this->publishCommand);
        $tester->execute([
            Option::BOOK_DIR => $this->tmpDir,
        ]);

        $this->assertFileExists($this->tmpDir . '/output/epub');
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/config/publish-config.yml';
    }
}
