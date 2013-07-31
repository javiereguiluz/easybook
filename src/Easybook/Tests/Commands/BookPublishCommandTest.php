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

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Filesystem\Filesystem;
use Easybook\DependencyInjection\Application;
use Easybook\Console\ConsoleApplication;
use Easybook\Console\Command\BookNewCommand;
use Easybook\Console\Command\BookPublishCommand;

class BookPublishCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $console;
    protected $filesystem;
    protected $tmpDir;

    public function setUp()
    {
        // create the console application and add the tested command
        $app = new Application();
        $this->console = new ConsoleApplication($app);
        $this->console->add(new BookNewCommand());
        $this->console->add(new BookPublishCommand());

        // setup temp dir for generated files
        $this->tmpDir = $app['app.dir.cache'].'/'.uniqid('phpunit_', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpDir);

        // generate a sample book before testing its publication
        $command = $this->console->find('new');
        $tester  = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            'title'   => 'The Origin of Species',
            '--dir'   => $this->tmpDir
        ));
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testCommandDisplaysApplicationSignature()
    {
        $command = $this->console->find('publish');

        $tester  = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            'slug'    => 'the-origin-of-species',
            'edition' => 'web',
            '--dir'   => $this->tmpDir,
        ));

        $app = $command->getApp();

        $this->assertContains($app['app.signature'], $command->asText(),
            'The command text description displays the application signature.'
        );
    }

    public function testInteractiveCommand()
    {
        $command = $this->console->find('publish');

        // prepare the data that will be input interactively
        // code copied from Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest.php
        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream("\n\nthe-origin-of-species\n\n\nweb\n"));
        $helper = new HelperSet(array(new FormatterHelper(), $dialog));
        $command->setHelperSet($helper);

        $tester  = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            '--dir'   => $this->tmpDir
        ), array(
            'interactive' => true
        ));

        $app = $command->getApp();

        $this->assertContains(
            $app['app.signature'],
            $tester->getDisplay(),
            'The interactive publisher displays the application signature'
        );

        $this->assertContains(
            'Welcome to the easybook interactive book publisher',
            $tester->getDisplay(),
            'The interactive publisher welcome message is shown'
        );

        $this->assertContains(
            'Please, type the slug of the book (e.g. the-origin-of-species)',
            $tester->getDisplay(),
            'The interactive generator asks for the title of the book'
        );

        $this->assertContains(
            'ERROR: The slug can only contain letters, numbers and dashes (no spaces)',
            $tester->getDisplay(),
            'Interactive publisher validates wrong "slug" input'
        );

        $this->assertContains(
            'Publishing web edition of The Origin of Species book...',
            $tester->getDisplay(),
            'The book is being published'
        );

        $this->assertContains(
            'OK  You can access the book in the following directory:',
            $tester->getDisplay(),
            'The book is successfully published'
        );

        $this->assertContains(
            '/the-origin-of-species/Output/web',
            $tester->getDisplay(),
            'The book is published in the proper directory'
        );
    }

    /**
     * @dataProvider getNonInteractiveCommandData
     */
    public function testNonInteractiveCommand($edition, $publishedBookFilePath, $maxTimeElapsed)
    {
        $command = $this->console->find('publish');
        $tester  = new CommandTester($command);

        $start = microtime(true);
        $tester->execute(array(
            'command' => $command->getName(),
            'slug'    => 'the-origin-of-species',
            'edition' => $edition,
            '--dir'   => $this->tmpDir
        ));
        $finish = microtime(true);

        $this->assertContains(
            sprintf('Publishing %s edition of The Origin of Species book', $edition),
            $tester->getDisplay(),
            sprintf('The "%s" edition of the sample book has been published', $edition)
        );

        $this->assertFileExists(
            sprintf('%s/the-origin-of-species/Output/%s', $this->tmpDir, $publishedBookFilePath),
            sprintf('The book has been published as %s', $publishedBookFilePath)
        );

        $this->assertLessThan($maxTimeElapsed, $finish - $start,
            sprintf('The publication of "%s" edition took less than %s seconds', $edition, $maxTimeElapsed)
        );
    }

    public function getNonInteractiveCommandData()
    {
        return array(
            //    edition    $publishedBookFilePath     maxTimeElapsed
            array('web',     'web/book.html',           5),
            array('website', 'website/book/index.html', 5),
            array('ebook',   'ebook/book.epub',         5),
        );
    }

    public function testNonInteractionInvalidBookAndEdition()
    {
        $command = $this->console->find('publish');
        $tester  = new CommandTester($command);

        try {
            $tester->execute(array(
                'command' => $command->getName(),
                'slug'    => uniqid('non_existent_book_'),
                'edition' => uniqid('non_existent_edition_'),
                '--dir'   => $this->tmpDir,
                '--no-interaction' => true
            ), array(
                'interactive' => false
            ));
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf('\RuntimeException', $e);
            $this->assertContains('The directory of the book cannot be found', $e->getMessage());
        }
    }

    public function testNonInteractionInvalidEdition()
    {
        $command = $this->console->find('publish');
        $tester  = new CommandTester($command);

        try {
            $tester->execute(array(
                'command' => $command->getName(),
                'slug'    => 'the-origin-of-species',
                'edition' => uniqid('non_existent_edition_'),
                '--dir'   => $this->tmpDir,
                '--no-interaction' => true
            ), array(
                'interactive' => false
            ));
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf('\RuntimeException', $e);
            $this->assertContains('edition isn\'t defined', $e->getMessage());
        }
    }

    public function testBeforeAndAfterPublishScripts()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped("This test executes commands not available for Windows systems.");
        }

        $bookConfigurationViaCommand = array(
            'book' => array(
                'title' => 'My Custom Title',
                'editions' => array(
                    'web' => array(
                        'before_publish' => array(
                            "touch before_publish_script.txt",
                            "echo '123' > before_publish_script.txt",
                            "touch {{ 'other' ~ '_before_publish_script' ~ '.txt' }}",
                            "echo '{{ book.title|upper }}' > other_before_publish_script.txt",
                        ),
                        'after_publish' => array(
                            "touch after_publish_script.txt",
                            "echo '456' > after_publish_script.txt",
                            "touch {{ 'other' ~ '_after_publish_script' ~ '.txt' }}",
                            "echo '{{ book.title[0:9]|upper }}' > other_after_publish_script.txt",
                        ),
                    )
                )
            )
        );

        $command = $this->console->find('publish');
        $tester  = new CommandTester($command);

        $tester->execute(array(
            'command' => $command->getName(),
            'slug'    => 'the-origin-of-species',
            'edition' => 'web',
            '--dir'   => $this->tmpDir,
            '--no-interaction' => true,
            '--configuration'  => json_encode($bookConfigurationViaCommand)
        ), array(
            'interactive' => false
        ));

        $bookDir = $this->tmpDir.'/the-origin-of-species';

        $this->assertFileExists($bookDir.'/before_publish_script.txt');
        $this->assertEquals("123\n", file_get_contents($bookDir.'/before_publish_script.txt'));
        $this->assertFileExists($bookDir.'/other_before_publish_script.txt');
        $this->assertEquals("MY CUSTOM TITLE\n", file_get_contents($bookDir.'/other_before_publish_script.txt'));

        $this->assertFileExists($bookDir.'/after_publish_script.txt');
        $this->assertEquals("456\n", file_get_contents($bookDir.'/after_publish_script.txt'));
        $this->assertFileExists($bookDir.'/other_after_publish_script.txt');
        $this->assertEquals("MY CUSTOM\n", file_get_contents($bookDir.'/other_after_publish_script.txt'));
    }

    public function testFailingBeforePublishScript()
    {
        $bookConfigurationViaCommand = array(
            'book' => array(
                'editions' => array(
                    'web' => array(
                        'before_publish' => array(
                            uniqid('this_command_does_not_exist_')
                        )
                    )
                )
            )
        );

        $command = $this->console->find('publish');
        $tester  = new CommandTester($command);

        try {
            $tester->execute(array(
                'command' => $command->getName(),
                'slug'    => 'the-origin-of-species',
                'edition' => 'web',
                '--dir'   => $this->tmpDir,
                '--no-interaction' => true,
                '--configuration'  => json_encode($bookConfigurationViaCommand)
            ), array(
                'interactive' => false
            ));
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf('\RuntimeException', $e);
            $this->assertContains('There was an error executing the following script', $e->getMessage());
        }
    }

    // code copied from Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest.php
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input.str_repeat("\n", 10));
        rewind($stream);

        return $stream;
    }
}
