<?php declare(strict_types=1);

namespace Easybook\Tests\Console\Command;

use Easybook\Console\Command\NewCommand;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

final class NewCommandTest extends AbstractContainerAwareTestCase
{
    /**
     * @var string
     */
    private $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/_easybook_tests/' . uniqid();
    }

    public function testCommand(): void
    {
        $tester = $this->createNewBook();

        $this->assertRegExp(
            '/You can start writing your book in the following directory/',
            $tester->getDisplay(),
            'The book skeleton has been generated'
        );

        $this->assertRegExp(
            '/.*\/the-origin-of-species/',
            $tester->getDisplay(),
            'The name of the new book directory is correct'
        );
    }

    public function testBookSkeletonIsProperlyGenerated(): void
    {
        $tester = $this->createNewBook();
        $bookDir = $this->tmpDir . '/the-origin-of-species';

        $files = ['config.yml', 'Contents/chapter1.md', 'Contents/chapter2.md', 'Contents/images', 'Output'];

        foreach ($files as $file) {
            $this->assertFileExists($bookDir . '/' . $file, sprintf('%s has been generated', $file));
        }
    }

    public function testBookSkeletonContents(): void
    {
        $tester = $this->createNewBook();

        $bookDir = $this->tmpDir . '/the-origin-of-species';
        $bookConfig = Yaml::parse($bookDir . '/config.yml');

        $this->assertArraySubset(['book' => [
            'title' => 'The Origin of Species',
            'author' => 'Change this: Author Name',
            'edition' => 'First edition',
            'language' => 'en',
            'publication_date' => null,
        ]], $bookConfig, false, 'The basic book configuration is properly generated.');

        $this->assertArraySubset(['book' => ['contents' => [
            ['element' => 'cover'],
            ['element' => 'toc'],
            [
                'element' => 'chapter',
                'number' => 1,
                'content' => 'chapter1.md',
            ],
            [
                'element' => 'chapter',
                'number' => 2,
                'content' => 'chapter2.md',
            ],
        ]]], $bookConfig, false, 'The book contents configuration is properly generated.');

        $this->assertSame(
            ['ebook', 'kindle', 'print', 'web', 'website'],
            array_keys($bookConfig['book']['editions']),
            'The book editions configuration is properly generated.'
        );
    }

    public function testGenerateTheSameBookTwoConsecutivetimes(): void
    {
        // first book
        $this->createNewBook();

        // second book
        $tester = $this->createNewBook();

        $this->assertRegExp(
            '/You can start writing your book in the following directory/',
            $tester->getDisplay(),
            'The second book skeleton has been generated'
        );

        $this->assertRegExp(
            '/.*\/the-origin-of-species-\d{1}\s+/',
            $tester->getDisplay(),
            'The name of the second book directory is correct'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNonexistentDir(): void
    {
        /** @var NewCommand $command */
        $command = $this->container->get(NewCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            '--dir' => './' . uniqid('non_existent_dir'),
        ]);
    }

    private function createNewBook(): CommandTester
    {
        /** @var NewCommand $bookNewCommand */
        $bookNewCommand = $this->container->get(NewCommand::class);
        $tester = new CommandTester($bookNewCommand);

        $tester->execute([
            'command' => $bookNewCommand->getName(),
            'title' => 'The Origin of Species',
            '--dir' => $this->tmpDir,
        ]);

        return $tester;
    }
}
