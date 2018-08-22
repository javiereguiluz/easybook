<?php declare(strict_types=1);

namespace Easybook\Tests\Commands;

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
//    /**
//     * @var string
//     */
    //    private $tmpDir;

    protected function setUp(): void
    {
        // generate a sample book before testing its publication

        /** @var BookNewCommand $bookNewCommand */
        $bookNewCommand = $this->container->get(BookNewCommand::class);
        $tester = new CommandTester($bookNewCommand);
        $tester->execute([
            'command' => $bookNewCommand->getName(),
            'title' => 'The Origin of Species',
            //            '--dir' => $this->tmpDir,
        ]);
    }

//    public function tearDown()
//    {
//        $this->filesystem->remove($this->tmpDir);
    //    }

    /**
     * @dataProvider getCommandData
     */
    public function testCommand($edition, $publishedBookFilePath): void
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

    public function getCommandData()
    {
        return [
            //    edition    $publishedBookFilePath
            ['web', 'web/book.html'],
            ['website', 'website/book/index.html'],
            ['ebook', 'ebook/book.epub'],
        ];
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
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('This test executes commands not available for Windows systems.');
        }

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

        $command = $this->container->get(BookPublishCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'command' => $command->getName(),
            'slug' => 'the-origin-of-species',
            'edition' => 'web',
            '--dir' => $this->tmpDir,
            '--no-interaction' => true,
            '--configuration' => json_encode($bookConfigurationViaCommand),
        ], [
            'interactive' => false,
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
        $bookConfigurationViaCommand = [
            'book' => [
                'editions' => [
                    'web' => [
                        'before_publish' => [uniqid('this_command_does_not_exist_')],
                    ],
                ],
            ],
        ];

        $command = $this->container->get(BookPublishCommand::class);
        $tester = new CommandTester($command);

        try {
            $tester->execute([
                'command' => $command->getName(),
                'slug' => 'the-origin-of-species',
                'edition' => 'web',
                '--dir' => $this->tmpDir,
                '--no-interaction' => true,
                '--configuration' => json_encode($bookConfigurationViaCommand),
            ], [
                'interactive' => false,
            ]);
        } catch (RuntimeException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e);
            $this->assertContains('There was an error executing the following script', $e->getMessage());
        }
    }

    // code copied from Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest.php
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input . str_repeat("\n", 10));
        rewind($stream);

        return $stream;
    }

    private function publishBook($edition = 'web', $slug = 'the-origin-of-species'): CommandTester
    {
        $command = $this->container->get(BookPublishCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'command' => $command->getName(),
            'slug' => $slug,
            'edition' => $edition,
            '--dir' => $this->tmpDir,
        ], [
            'interactive' => false,
        ]);

        return $tester;
    }
}
