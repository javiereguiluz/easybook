<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\Toolkit;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class PublisherTest extends AbstractContainerAwareTestCase
{
    private $app;

    private $filesystem;

    private $tmpDir;

    protected function setUp(): void
    {
        // setup temp dir for generated files
        $this->tmpDir = $this->app['app.dir.cache'] . '/' . uniqid('phpunit_', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpDir);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);

        parent::tearDown();
    }

    public function testBookPublish(): void
    {
        // find the test books
        $books = $this->app['finder']
            ->directories()
            ->name('book*')
            ->depth(0)
            ->in(__DIR__ . '/fixtures');

        foreach ($books as $book) {
            $this->markTestSkipped(
                'Temporarily marked as skipeed until we update these tests to be less fragile with whitespaces.'
            );

            $slug = $book->getFileName();
            if ($slug === 'book5' && (version_compare(PHP_VERSION, '5.4.0', '<') || ! extension_loaded('intl'))) {
                $this->markTestSkipped(
                    'This test requires PHP 5.4.0+ with the intl extension enabled (the book contains a lot of non-latin characters that need the native PHP transliterator)'
                );
            }

            // mirror test book contents in temp dir
            $this->filesystem->mirror(__DIR__ . '/fixtures/' . $slug . '/input', $this->tmpDir . '/' . $slug);

            // look for and publish all the book editions
            $bookConfig = Yaml::parse($this->tmpDir . '/' . $slug . '/config.yml');
            $editionNames = array_keys($bookConfig['book']['editions']);

            foreach ($editionNames as $editionName) {
                // publish each book edition
                $input = new ArrayInput([
                    'command' => 'publish',
                    'slug' => $slug,
                    'edition' => $editionName,
                    '--dir' => $this->tmpDir,
                ]);

                $console->find('publish')->run($input, new NullOutput());

                // assert that generated files are exactly the same as expected
                $generatedFiles = $this->app['finder']
                    ->files()
                    ->notName('.gitignore')
                    ->in($this->tmpDir . '/' . $slug . '/Output/' . $editionName);

                foreach ($generatedFiles as $file) {
                    if ($file->getExtension() === 'epub') {
                        // unzip both files to compare its contents
                        $workDir = $this->tmpDir . '/' . $slug . '/unzip/' . $editionName;
                        $generated = $workDir . '/generated';
                        $expected = $workDir . '/expected';

                        Toolkit::unzip($file->getRealPath(), $generated);
                        Toolkit::unzip(__DIR__ . '/fixtures/' . $slug . '/expected/' .
                                    $editionName . '/' . $file->getRelativePathname(), $expected);

                        // assert that generated files are exactly the same as expected
                        $genFiles = $this->app['finder']
                            ->files()
                            ->notName('.gitignore')
                            ->in($generated);

                        foreach ($genFiles as $genFile) {
                            $this->assertFileEquals(
                                $expected . '/' . $genFile->getRelativePathname(),
                                $genFile->getPathname(),
                                sprintf(
                                    "ERROR on ${book}:\n '%s' file (into ZIP file '%s') not properly generated",
                                    $genFile->getRelativePathname(),
                                    $file->getPathName()
                                )
                            );
                        }

                        // assert that all required files are generated
                        $this->checkForMissingFiles($expected, $generated);
                    } else {
                        $this->assertFileEquals(
                            __DIR__ . '/fixtures/' . $slug . '/expected/' . $editionName . '/' . $file->getRelativePathname(),
                            $file->getPathname(),
                            sprintf("'%s' file not properly generated", $file->getPathname())
                        );
                    }
                }

                // assert that all required files are generated
                $this->checkForMissingFiles(
                    __DIR__ . '/fixtures/' . $slug . '/expected/' . $editionName,
                    $this->tmpDir . '/' . $slug . '/Output/' . $editionName
                );

                // reset app state before the next publishing
//                $this->app = new Application();
//                $console = new ConsoleApplication($this->app);
            }
        }
    }

    /*
     * Assert that all expected files were generated
     */
    protected function checkForMissingFiles($dirExpected, $dirGenerated): void
    {
        $expectedFiles = $this->app['finder']
            ->files()
            ->notName('.gitignore')
            ->in($dirExpected);

        foreach ($expectedFiles as $file) {
            $this->assertFileExists(
                $dirGenerated . '/' . $file->getRelativePathname(),
                sprintf("'%s' file has not been generated", $file->getPathname())
            );
        }
    }
}
