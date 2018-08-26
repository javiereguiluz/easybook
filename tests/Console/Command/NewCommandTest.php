<?php declare(strict_types=1);

namespace Easybook\Tests\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Console\Command\NewCommand;
use Easybook\Exception\Filesystem\DirectoryNotEmptyException;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

final class NewCommandTest extends AbstractContainerAwareTestCase
{
    /**
     * @var string
     */
    private $bookDirectory;

    protected function setUp(): void
    {
        $this->bookDirectory = sys_get_temp_dir() . '/_easybook_tests/' . uniqid() . '/book/my-first-book';

        $symfonyStyle = $this->container->get(SymfonyStyle::class);
        $symfonyStyle->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->bookDirectory);
    }

    public function testCommand(): void
    {
        $this->createNewBook($this->bookDirectory);

        $this->assertTrue($this->filesystem->exists($this->bookDirectory));

        $files = ['config.yml', 'Contents/chapter1.md', 'Contents/chapter2.md'];
        foreach ($files as $file) {
            $this->assertFileExists($this->bookDirectory . DIRECTORY_SEPARATOR . $file);
        }
    }

    public function testGenerateTheSameBookTwoConsecutivetimes(): void
    {
        $this->createNewBook($this->bookDirectory);

        $this->expectException(DirectoryNotEmptyException::class);
        $this->createNewBook($this->bookDirectory);
    }

    private function createNewBook(string $bookDir): CommandTester
    {
        /** @var NewCommand $bookNewCommand */
        $bookNewCommand = $this->container->get(NewCommand::class);
        $tester = new CommandTester($bookNewCommand);

        $tester->execute([
            Option::DIR => $bookDir,
        ]);

        return $tester;
    }
}
