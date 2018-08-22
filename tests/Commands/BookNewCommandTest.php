<?php declare(strict_types=1);

namespace Easybook\Tests\Commands;

use Easybook\Console\Command\BookNewCommand;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class BookNewCommandTest extends AbstractContainerAwareTestCase
{
    protected $filesystem;

    protected $tmpDir;

    protected $console;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    protected function setUp(): void
    {
        $this->questionHelper = $this->container->get(QuestionHelper::class);
    }
    // setup temp dir for generated files
//        $this->tmpDir = $app['app.dir.cache'].'/'.uniqid('phpunit_', true);
//        $this->filesystem = new Filesystem();
//        $this->filesystem->mkdir($this->tmpDir);
//    }

//    public function tearDown()
//    {
//        $this->filesystem->remove($this->tmpDir);
    //    }

    public function testInteractiveCommand(): void
    {
        $command = $this->container->get(BookNewCommand::class);

        // prepare the data that will be input interactively
        // code copied from Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest.php
//        $dialog = new DialogHelper();
//        $this->questionHelper->setInputStream($this->getInputStream("\n\nThe Origin of Species\n"));

//        $helper = new HelperSet([new FormatterHelper(), $dialog]);

        //        $command->setHelperSet($helper);

        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            '--dir' => $this->tmpDir,
        ], [
            'interactive' => true,
        ]);

        $this->assertContains(
            'ERROR: The title cannot be empty.',
            $tester->getDisplay(),
            'The interactive generator validates wrong title input'
        );

        $this->assertContains(
            'Welcome to the easybook interactive book generator',
            $tester->getDisplay(),
            'The interactive generator welcome message is shown'
        );

        $this->assertContains(
            'Please, type the title of the book (e.g. The Origin of Species)',
            $tester->getDisplay(),
            'The interactive generator asks for the title of the book'
        );

        $this->assertContains(
            'OK  You can start writing your book in the following directory',
            $tester->getDisplay(),
            'Interactive book generation is successfully completed'
        );

        $this->assertContains(
            'the-origin-of-species',
            $tester->getDisplay(),
            'The book is generated in the proper directory'
        );
    }

    public function testNonInteractiveCommand(): void
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
