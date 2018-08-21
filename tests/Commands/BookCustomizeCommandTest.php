<?php declare(strict_types=1);

namespace Easybook\Tests\Commands;

use Easybook\Console\Command\BookCustomizeCommand;
use Easybook\Console\Command\BookNewCommand;
use Easybook\Console\Command\BookPublishCommand;
use Easybook\Tests\AbstractContainerAwareTestCase;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class BookCustomizeCommandTest extends AbstractContainerAwareTestCase
{
    private $console;

    /**
     * @var Filesystem
     */
    private $filesystem;

    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->container->get(BookNewCommand::class);

        // setup temp dir for generated files
        $this->tmpDir = $app['app.dir.cache'] . '/' . uniqid('phpunit_', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpDir);

        // generate a sample book before testing its customization
        $command = $this->container->get(BookNewCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            'title' => 'The Origin of Species',
            '--dir' => $this->tmpDir,
        ]);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testInteractiveCommand(): void
    {
        $command = $this->container->get(BookCustomizeCommand::class);

        // prepare the data that will be input interactively
        // code copied from Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest.php
        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream("\n\nthe-origin-of-species\n\n\nweb\n"));
        $helper = new HelperSet([new FormatterHelper(), $dialog]);
        $command->setHelperSet($helper);

        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            '--dir' => $this->tmpDir,
        ], [
            'interactive' => true,
        ]);

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
    public function testNonInteractiveCommand($edition): void
    {
        $tester = $this->customizeBook($edition);

        $this->assertContains(
            'You can now customize the book design with the following stylesheet',
            $tester->getDisplay(),
            sprintf('The "%s" edition of the sample book has been customized', $edition)
        );

        $skeletonCss = sprintf(
            '%s/Customization/%s/style.css',
            $app['app.dir.skeletons'],
            $app->edition('format')
        );
        $generatedCss = sprintf('%s/%s/style.css', $app['publishing.dir.templates'], $edition);

        $this->assertFileEquals(
            $skeletonCss,
            $generatedCss,
            sprintf('The generated CSS stylesheet for %s edition is correct', $edition)
        );
    }

    public function getNonInteractiveCommandData()
    {
        return [['web'], ['website'], ['print'], ['ebook']];
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage ERROR: The directory of the book cannot be found.
     */
    public function testNonInteractionInvalidBookAndEdition(): void
    {
        $this->customizeBook(uniqid('non_existent_edition_'), uniqid('non_existent_book_'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp /ERROR: The '.*' edition isn't defined for\n'The Origin of Species' book./
     */
    public function testNonInteractionInvalidEdition(): void
    {
        $this->customizeBook(uniqid('non_existent_edition_'));
    }

    public function testFailingCustomizationforABookThatAlreadyContainsCustomStyles(): void
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('This test executes commands not available for Windows systems.');
        }

        // this configuration creates a 'style.css' file to simulate that the
        // customization CSS file has already been defined
        $bookConfigurationViaCommand = [
            'book' => [
                'editions' => [
                    'web' => [
                        'before_publish' => [
                            'mkdir -p Resources/Templates/web/',
                            'touch Resources/Templates/web/style.css',
                        ],
                    ],
                ],
            ],
        ];

        // publish the sample book before testing its customization
        $command = $this->container->get(BookPublishCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([
            'command' => $command->getName(),
            'slug' => 'the-origin-of-species',
            'edition' => 'web',
            '--dir' => $this->tmpDir,
            '--configuration' => json_encode($bookConfigurationViaCommand),
        ]);

        $command = $this->container->get(BookCustomizeCommand::class);
        $tester = new CommandTester($command);

        try {
            $tester->execute([
                'command' => $command->getName(),
                'slug' => 'the-origin-of-species',
                'edition' => 'web',
                '--dir' => $this->tmpDir,
            ], [
                'interactive' => false,
            ]);
        } catch (RuntimeException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e);
            $this->assertContains('edition already contains a custom CSS stylesheet', $e->getMessage());
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

    private function customizeBook($edition = 'web', $slug = 'the-origin-of-species'): CommandTester
    {
        $command = $this->container->get(BookCustomizeCommand::class);
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
