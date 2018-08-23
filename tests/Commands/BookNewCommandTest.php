<?php declare(strict_types=1);

namespace Easybook\Tests\Commands;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

final class BookNewCommandTest extends AbstractContainerAwareTestCase
{
    protected $filesystem;

    protected $tmpDir;

    protected $console;

    public function testCommand(): void
    {
        $tester = $this->createNewBook();

        $this->assertRegExp(
            '/.* OK .* You can start writing your book in the following directory/',
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
        ]], $bookConfig, 'The basic book configuration is properly generated.');

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
        ]]], $bookConfig, 'The book contents configuration is properly generated.');

        $this->assertSame(
            ['ebook', 'kindle', 'print', 'web', 'website'],
            array_keys($bookConfig['book']['editions']),
            'The book editions configuration is properly generated.'
        );
    }

    public function testGenerateTheSameBookTwoConsecutivetimes(): void
    {
        $tester = $this->createNewBook();
        $tester = $this->createNewBook();

        $this->assertRegExp(
            '/.* OK .* You can start writing your book in the following directory/',
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
        $command = $this->console->find('new');
        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            'title' => 'The Origin of Species',
            '--dir' => './' . uniqid('non_existent_dir'),
        ]);
    }

    // code copied from Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest.php
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input . str_repeat("\n", 10));
        rewind($stream);

        return $stream;
    }

    private function createNewBook(): CommandTester
    {
        $command = $this->console->find('new');
        $tester = new CommandTester($command);

        $tester->execute([
            'command' => $command->getName(),
            'title' => 'The Origin of Species',
            '--dir' => $this->tmpDir,
        ]);

        return $tester;
    }
}
