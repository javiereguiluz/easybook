<?php declare(strict_types=1);

namespace Easybook\Tests\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Console\Command\NewCommand;
use Easybook\Console\Command\PublishCommand;
use Easybook\Exception\Process\BeforeOrAfterPublishScriptFailedException;
use Easybook\Tests\AbstractCustomConfigContainerAwareTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

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

        // to prevent output in tests
        /** @var SymfonyStyle $symfonyStyle */
        $symfonyStyle = $this->container->get(SymfonyStyle::class);
        $symfonyStyle->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        $newCommand = $this->container->get(NewCommand::class);
        (new CommandTester($newCommand))->execute([
            Option::BOOK_DIR => $this->bookDirectory,
        ]);

        $this->bookPublishCommand = $this->container->get(PublishCommand::class);
    }

    public function testFailingBeforePublishScript(): void
    {
        $this->expectException(BeforeOrAfterPublishScriptFailedException::class);

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
