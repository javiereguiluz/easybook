<?php declare(strict_types=1);

namespace Easybook\Tests\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Console\Command\NewCommand;
use Easybook\Console\Command\PublishCommand;
use Easybook\Tests\AbstractCustomConfigContainerAwareTestCase;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

final class ScriptsTest extends AbstractCustomConfigContainerAwareTestCase
{
    /**
     * @var PublishCommand
     */
    private $bookPublishCommand;

    /**
     * @var string
     */
    private $tmpDir;

    protected function setUp(): void
    {
        // generate a sample book before testing its publication
        $this->tmpDir = sys_get_temp_dir() . '/_easybook_tests/' . uniqid();

        // to prevent output in tests
        /** @var SymfonyStyle $symfonyStyle */
        $symfonyStyle = $this->container->get(SymfonyStyle::class);
        $symfonyStyle->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        $newCommand = $this->container->get(NewCommand::class);
        $tester = new CommandTester($newCommand);
        $tester->execute([
            Option::BOOK_DIR => $this->tmpDir,
        ]);

        $this->bookPublishCommand = $this->container->get(PublishCommand::class);
    }

    public function testFailingBeforePublishScript(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('There was an error executing the following script');

        $tester = new CommandTester($this->bookPublishCommand);
        $tester->execute([
            Option::BOOK_DIR => $this->tmpDir,
        ]);
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/config/scripts.yml';
    }
}
