<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Commands;

use Easybook\DependencyInjection\Application;
use Easybook\Console\ConsoleApplication;
use Easybook\Console\Command\BookNewCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class BookNewCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;
    protected $tmpDir;
    protected $console;

    public function setUp()
    {
        // create the console application and add the tested command
        $app = new Application();
        $this->console = new ConsoleApplication($app);
        $this->console->add(new BookNewCommand());

        // setup temp dir for generated files
        $this->tmpDir = $app['app.dir.cache'].'/'.uniqid('phpunit_', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpDir);
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testInteractiveCommand()
    {
        $command = $this->console->find('new');

        // prepare the data that will be input interactively
        // code copied from Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest.php
        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream("\n\nThe Origin of Species\n"));
        $helper = new HelperSet(array(new FormatterHelper(), $dialog));
        $command->setHelperSet($helper);

        $tester = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            '--dir' => $this->tmpDir,
        ), array(
            'interactive' => true,
        ));

        $app = $command->getApp();

        $this->assertContains(
            $app['app.signature'],
            $tester->getDisplay(),
            'The interactive generator displays the application signature'
        );

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

    public function testNonInteractiveCommand()
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

    public function testBookSkeletonIsProperlyGenerated()
    {
        $tester = $this->createNewBook();
        $bookDir = $this->tmpDir.'/the-origin-of-species';

        $files = array(
            'config.yml',
            'Contents/chapter1.md',
            'Contents/chapter2.md',
            'Contents/images',
            'Output',
        );

        foreach ($files as $file) {
            $this->assertFileExists($bookDir.'/'.$file, sprintf('%s has been generated', $file));
        }
    }

    public function testBookSkeletonContents()
    {
        $tester = $this->createNewBook();

        $bookDir = $this->tmpDir.'/the-origin-of-species';
        $bookConfig = Yaml::parse($bookDir.'/config.yml');

        $this->assertArraySubset(array('book' => array(
            'title' => 'The Origin of Species',
            'author' => 'Change this: Author Name',
            'edition' => 'First edition',
            'language' => 'en',
            'publication_date' => null,
        )), $bookConfig, 'The basic book configuration is properly generated.');

        $this->assertArraySubset(array('book' => array('contents' => array(
            array('element' => 'cover'),
            array('element' => 'toc'),
            array('element' => 'chapter', 'number' => 1, 'content' => 'chapter1.md'),
            array('element' => 'chapter', 'number' => 2, 'content' => 'chapter2.md'),
        ))), $bookConfig, 'The book contents configuration is properly generated.');

        $this->assertEquals(
            array('ebook', 'kindle', 'print', 'web', 'website'),
            array_keys($bookConfig['book']['editions']),
            'The book editions configuration is properly generated.'
        );
    }

    public function testGenerateTheSameBookTwoConsecutivetimes()
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
    public function testNonexistentDir()
    {
        $command = $this->console->find('new');
        $tester = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            'title' => 'The Origin of Species',
            '--dir' => './'.uniqid('non_existent_dir'),
        ));
    }

    // code copied from Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest.php
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input.str_repeat("\n", 10));
        rewind($stream);

        return $stream;
    }

    /**
     * @return CommandTester
     */
    private function createNewBook()
    {
        $command = $this->console->find('new');
        $tester = new CommandTester($command);

        $tester->execute(array(
            'command' => $command->getName(),
            'title' => 'The Origin of Species',
            '--dir' => $this->tmpDir,
        ));

        return $tester;
    }
}
