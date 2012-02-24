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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class BookNewCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;
    protected $tmpDir;
    protected $console;

    public function setUp()
    {
        // setup temp dir for generated files
        $this->tmpDir = sys_get_temp_dir().'/easybook';
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpDir);

        // create the console application and add the tested command
        $app = new Application();
        $this->console = new ConsoleApplication($app);
        $this->console->add(new BookNewCommand());
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testNonInteractiveExecute()
    {
        $command = $this->console->find('new');
        $tester  = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            'title'   => 'The Origin of Species',
            '--dir'   => $this->tmpDir
        ));

        $this->assertRegExp(
            '/.* OK .* You can start writing your book in the following directory/',
            $tester->getDisplay(),
            'The book skeleton has been generated'
        );

        $this->assertRegExp(
            '/.*\/easybook\/the-origin-of-species\s+/',
            $tester->getDisplay(),
            'The name of the new book directory is correct'
        );

        $bookDir = $this->tmpDir.'/the-origin-of-species';

        $files = array(
            'config.yml',
            'Contents/chapter1.md',
            'Contents/chapter2.md',
            'Contents/images',
            'Output'
        );
        foreach ($files as $file) {
            $this->assertFileExists(
                $bookDir.'/'.$file,
                sprintf('%s has been generated', $file)
            );
        }

        $bookConfig = Yaml::parse($bookDir.'/config.yml');
        
        // --- test basic config ----------------------------------------------
        $this->assertEquals(
            $bookConfig['book']['title'],
            'The Origin of Species',
            'The title of the new book is "The Origin of Species"'
        );
        
        $this->assertEquals(
            $bookConfig['book']['author'],
            'Change this: Author Name',
            'The author of the new book is unset'
        );
        
        $this->assertEquals(
            $bookConfig['book']['language'],
            'en',
            'The language of the new book is English'
        );
        
        $this->assertEquals(
            $bookConfig['book']['publication_date'],
            null,
            'The publication date of the new book is unset'
        );

        // --- test contents config -------------------------------------------
        $contents = array(
            array('element' => 'cover'),
            array('element' => 'toc'),
            array('element' => 'chapter', 'number' => 1, 'content' => 'chapter1.md'),
            array('element' => 'chapter', 'number' => 2, 'content' => 'chapter2.md'),
        );
        $this->assertEquals(
            $bookConfig['book']['contents'],
            $contents,
            'The default contents of the new book are correct'
        );

        // --- test editions config -------------------------------------------
        $this->assertEquals(
            count($bookConfig['book']['editions']),
            4,
            'The new book has 4 editions configured'
        );
        
        $this->assertArrayHasKey(
            'ebook',
            $bookConfig['book']['editions'],
            'The new book has an "ebook" (.epub) edition'
        );
        $this->assertArrayHasKey(
            'print',
            $bookConfig['book']['editions'],
            'The new book has a "print" (.pdf) edition'
        );
        $this->assertArrayHasKey(
            'web',
            $bookConfig['book']['editions'],
            'The new book has a "web" (.html) edition'
        );
        $this->assertArrayHasKey(
            'website',
            $bookConfig['book']['editions'],
            'The new book has a "website"(.html) edition'
        );

        // --- test second book generation ------------------------------------
        $tester->execute(array(
            'command' => $command->getName(),
            'title'   => 'The Origin of Species',
            '--dir'   => $this->tmpDir
        ));

        $this->assertRegExp(
            '/.* OK .* You can start writing your book in the following directory/',
            $tester->getDisplay(),
            'The second book skeleton has been generated'
        );
        
        $this->assertRegExp(
            '/.*\/easybook\/the-origin-of-species-\d{1}\s+/',
            $tester->getDisplay(),
            'The name of the second book directory is correct'
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNonexistentDir()
    {
        $command = $this->console->find('new');
        $tester  = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            'title'   => 'The Origin of Species',
            '--dir'   => './'.uniqid('non_existent_dir')
        ));
    }
}
