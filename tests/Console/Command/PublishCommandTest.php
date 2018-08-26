<?php declare(strict_types=1);

namespace Easybook\Tests\Console\Command;

use Easybook\Configuration\Option;
use Easybook\Console\Command\NewCommand;
use Easybook\Console\Command\PublishCommand;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Iterator;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

final class PublishCommandTest extends AbstractContainerAwareTestCase
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

        $newCommand = $this->container->get(NewCommand::class);
        (new CommandTester($newCommand))->execute([
            Option::BOOK_DIR => $this->tmpDir,
        ]);

        $this->bookPublishCommand = $this->container->get(PublishCommand::class);
    }

//    /**
//     * @dataProvider getCommandData()
//     */
//    public function testCommand(string $edition, string $publishedBookFilePath): void
//    {
//        $this->publishBook($edition);
//
//        $this->assertFileExists(
//            sprintf('%s/the-origin-of-species/Output/%s', $this->tmpDir, $publishedBookFilePath),
//            sprintf('The book has been published as %s', $publishedBookFilePath)
//        );
//    }
//
//    public function getCommandData(): Iterator
//    {
//        yield ['ebook', 'ebook/book.epub'];
//    }
//
//    /**
//     * @expectedException RuntimeException
//     * @expectedExceptionMessageRegExp /ERROR: The '.*' edition isn't defined for\n'The Origin of Species' book./
//     */
//    public function testNonInteractionInvalidEdition(): void
//    {
//        $this->publishBook(uniqid('non_existent_edition_'));
//    }


//    private function publishBook(string $edition = 'web'): CommandTester
//    {
//        $command = $this->container->get(PublishCommand::class);
//        $tester = new CommandTester($command);
//
//        $tester->execute([
//            'command' => $command->getName(),
//            'edition' => $edition,
//            Option::self::BOOK_DIR => $this->tmpDir,
//        ]);
//
//        return $tester;
//    }
}
