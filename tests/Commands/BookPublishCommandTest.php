<?php declare(strict_types=1);

namespace Easybook\Tests\Commands;

use Iterator;
use Easybook\Console\Command\BookNewCommand;
use Easybook\Console\Command\BookPublishCommand;
use Easybook\Tests\AbstractContainerAwareTestCase;
use RuntimeException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class BookPublishCommandTest extends AbstractContainerAwareTestCase
{
    /**
     * @var BookNewCommand
     */
    private $bookNewCommand;

    /**
     * @var BookPublishCommand
     */
    private $bookPublishCommand;

    protected function setUp(): void
    {
        // generate a sample book before testing its publication

        $this->bookNewCommand = $this->container->get(BookNewCommand::class);
        $tester = new CommandTester($this->bookNewCommand);
        $tester->execute([
            'command' => $this->bookNewCommand->getName(),
            //            '--dir' => $this->tmpDir,
        ]);

        $this->bookPublishCommand = $this->container->get(BookPublishCommand::class);
    }

    /**
     * @dataProvider getCommandData()
     */
    public function testCommand(string $edition, string $publishedBookFilePath): void
    {
        $tester = $this->publishBook($edition);

        $this->assertContains(
            sprintf('Publishing %s edition of The Origin of Species book', $edition),
            $tester->getDisplay(),
            sprintf('The "%s" edition of the sample book has been published', $edition)
        );

        $this->assertFileExists(
            sprintf('%s/the-origin-of-species/Output/%s', $this->tmpDir, $publishedBookFilePath),
            sprintf('The book has been published as %s', $publishedBookFilePath)
        );
    }

    public function getCommandData(): Iterator
    {
        // edition, $publishedBookFilePath
        yield ['web', 'web/book.html'];
        yield ['website', 'website/book/index.html'];
        yield ['ebook', 'ebook/book.epub'];
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage ERROR: The directory of the book cannot be found.
     */
    public function testNonInteractionInvalidBookAndEdition(): void
    {
        $this->publishBook(uniqid('non_existent_edition_'), uniqid('non_existent_book_'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp /ERROR: The '.*' edition isn't defined for\n'The Origin of Species' book./
     */
    public function testNonInteractionInvalidEdition(): void
    {
        $this->publishBook(uniqid('non_existent_edition_'));
    }

    public function testBeforeAndAfterPublishScripts(): void
    {
        // @todo createWithConfig() test

        $bookConfigurationViaCommand = [
            'book' => [
                'title' => 'My Custom Title',
                'editions' => [
                    'web' => [
                        'before_publish' => [
                            'touch before_publish_script.txt',
                            "echo '123' > before_publish_script.txt",
                            "touch {{ 'other' ~ '_before_publish_script' ~ '.txt' }}",
                            "echo '{{ book.title|upper }}' > other_before_publish_script.txt",
                        ],
                        'after_publish' => [
                            'touch after_publish_script.txt',
                            "echo '456' > after_publish_script.txt",
                            "touch {{ 'other' ~ '_after_publish_script' ~ '.txt' }}",
                            "echo '{{ book.title[0:9]|upper }}' > other_after_publish_script.txt",
                        ],
                    ],
                ],
            ],
        ];

        $tester = new CommandTester($this->bookPublishCommand);

        $tester->execute([
            'command' => $this->bookPublishCommand->getName(),
            'edition' => 'web',
            '--dir' => $this->tmpDir,
        ]);

        $bookDir = $this->tmpDir . '/the-origin-of-species';

        $this->assertFileExists($bookDir . '/before_publish_script.txt');
        $this->assertSame("123\n", file_get_contents($bookDir . '/before_publish_script.txt'));
        $this->assertFileExists($bookDir . '/other_before_publish_script.txt');
        $this->assertSame("MY CUSTOM TITLE\n", file_get_contents($bookDir . '/other_before_publish_script.txt'));

        $this->assertFileExists($bookDir . '/after_publish_script.txt');
        $this->assertSame("456\n", file_get_contents($bookDir . '/after_publish_script.txt'));
        $this->assertFileExists($bookDir . '/other_after_publish_script.txt');
        $this->assertSame("MY CUSTOM\n", file_get_contents($bookDir . '/other_after_publish_script.txt'));
    }

    public function testFailingBeforePublishScript(): void
    {
        // @todo createWithConfig() test

        $bookConfigurationViaCommand = [
            'book' => [
                'editions' => [
                    'web' => [
                        'before_publish' => [uniqid('this_command_does_not_exist_')],
                    ],
                ],
            ],
        ];

        $tester = new CommandTester($this->bookPublishCommand);

        // $this->setExpected(...)
//        $this->assertInstanceOf(RuntimeException::class, $e);
//        $this->assertContains('There was an error executing the following script', $e->getMessage());

        $tester->execute([
            'command' => $this->bookPublishCommand->getName(),
            'edition' => 'web',
            '--dir' => $this->tmpDir,
        ]);
    }

    private function publishBook(string $edition = 'web'): CommandTester
    {
        $command = $this->container->get(BookPublishCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'command' => $command->getName(),
            'edition' => $edition,
            '--dir' => $this->tmpDir,
        ]);

        return $tester;
    }
}
