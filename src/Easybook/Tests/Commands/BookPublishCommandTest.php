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
use Easybook\Console\Command\BookPublishCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class BookPublishCommandTest extends \PHPUnit_Framework_TestCase
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
            'ERROR: The edition cannot be empty.',
            $tester->getDisplay(),
            'Interactive publisher validates wrong "edition" input'
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
    public function testNonInteractiveCommand($options, $expected)
    {
        list($publishedBook, $maxDuration) = $expected;
        $edition = $options['edition'];
        
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
            sprintf('%s/the-origin-of-species/Output/%s', $this->tmpDir, $publishedBook),
            sprintf('The book has been published as %s', $publishedBook)
        );

        $this->assertLessThan(
            $maxDuration,
            $finish - $start,
            sprintf('The publication of "%s" edition took less than %s seconds', $edition, $maxDuration)
        );
    }

    public function getNonInteractiveCommandData()
    {
        return array(
            array(array('edition' => 'web'), array('web/book.html', 5)),
            array(array('edition' => 'website'), array('website/book/index.html', 5)),
            array(array('edition' => 'ebook'), array('ebook/book.epub', 5)),
        );
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
