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
use Easybook\Console\Command\BookCustomizeCommand;

class BookCustomizeCommandTest extends \PHPUnit_Framework_TestCase
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
        $this->console->add(new BookCustomizeCommand());

        // setup temp dir for generated files
        $this->tmpDir = $app['app.dir.cache'].'/'.uniqid('phpunit_', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpDir);

        // generate a sample book before testing its customization
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
        $command = $this->console->find('customize');

        $tester  = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            'slug'    => 'the-origin-of-species',
            'edition' => 'web',
            '--dir'   => $this->tmpDir
        ));

        $app = $command->getApp();

        $this->assertContains($app['app.signature'], $command->asText(),
            'The command text description displays the application signature.'
        );
    }

    public function testInteractiveCommand()
    {
        $command = $this->console->find('customize');

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
            'The interactive customizer displays the application signature'
        );

        $this->assertContains(
            'Welcome to the easybook interactive book customizer',
            $tester->getDisplay(),
            'The interactive customizer welcome message is shown'
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
            'OK  You can now customize the book design with the following stylesheet:',
            $tester->getDisplay(),
            'The custom CSS is successfully generated'
        );
    }

    /**
     * @dataProvider getNonInteractiveCommandData
     */
    public function testNonInteractiveCommand($edition)
    {
        $command = $this->console->find('customize');
        $tester  = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            'slug'    => 'the-origin-of-species',
            'edition' => $edition,
            '--dir'   => $this->tmpDir
        ));

        $this->assertContains(
            'You can now customize the book design with the following stylesheet',
            $tester->getDisplay(),
            sprintf('The "%s" edition of the sample book has been customized', $edition)
        );

        $app = $this->console->getApp();

        $skeletonCss = sprintf('%s/Customization/%s/style.css',
            $app['app.dir.skeletons'],
            $app->edition('format')
        );
        $generatedCss = sprintf('%s/%s/style.css',
            $app['publishing.dir.templates'], $edition
        );

        $this->assertFileEquals($skeletonCss, $generatedCss,
            sprintf('The generated CSS stylesheet for %s edition is correct', $edition)
        );
    }

    public function getNonInteractiveCommandData()
    {
        return array(
            array('web'),
            array('website'),
            array('print'),
            array('ebook'),
        );
    }

    public function testNonInteractionInvalidBookAndEdition()
    {
        $command = $this->console->find('customize');
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
            $this->assertInstanceOf('RuntimeException', $e);
            $this->assertContains('The directory of the book cannot be found', $e->getMessage());
        }
    }

    public function testNonInteractionInvalidEdition()
    {
        $command = $this->console->find('customize');
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
             $this->assertInstanceOf('RuntimeException', $e);
             $this->assertContains('edition isn\'t defined', $e->getMessage());
        }
    }

    public function testFailingCustomizationforABookThatAlreadyContainsCustomStyles()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped("This test executes commands not available for Windows systems.");
        }

        // this configuration creates a 'style.css' file to simulate that the
        // customization CSS file has already been defined
        $bookConfigurationViaCommand = array(
            'book' => array(
                'editions' => array(
                    'web' => array(
                        'before_publish' => array(
                            "mkdir -p Resources/Templates/web/",
                            "touch Resources/Templates/web/style.css",
                        )
                    )
                )
            )
        );

        // publish the sample book before testing its customization
        $command = $this->console->find('publish');
        $tester  = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            'slug'    => 'the-origin-of-species',
            'edition' => 'web',
            '--dir'   => $this->tmpDir,
            '--configuration' => json_encode($bookConfigurationViaCommand)
        ));

        $command = $this->console->find('customize');
        $tester  = new CommandTester($command);

        try {
            $tester->execute(array(
                'command' => $command->getName(),
                'slug'    => 'the-origin-of-species',
                'edition' => 'web',
                '--dir'   => $this->tmpDir
            ), array(
                'interactive' => false
            ));
        } catch (\RuntimeException $e) {
             $this->assertInstanceOf('RuntimeException', $e);
             $this->assertContains('edition already contains a custom CSS stylesheet', $e->getMessage());
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
