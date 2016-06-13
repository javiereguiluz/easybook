<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Publishers;

use Easybook\Console\ConsoleApplication;
use Easybook\DependencyInjection\Application;
use Easybook\Tests\TestCase;
use Easybook\Util\Toolkit;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class PdfPublisherTest extends TestCase
{
    protected $app;
    protected $filesystem;
    protected $tmpDir;
    protected $isDebug;
    protected $needsManualVerification;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->isDebug = array_key_exists('debug', getopt('', array('debug')));
    }

    public function setUp()
    {
        $this->app = new Application();

        // setup temp dir for generated files
        if ($this->isDebug) {
            // reused
            $this->tmpDir = $this->app['app.dir.cache'] . '/' . 'phpunit';
        } else {
            // unique
            $this->tmpDir = $this->app['app.dir.cache'] . '/' . uniqid('phpunit_', true);
        }
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpDir);

        parent::setUp();
    }

    public function tearDown()
    {
        $delete = (!$this->isDebug || ($this->isDebug && !$this->hasFailed())) && !$this->needsManualVerification;

        if ($delete) {
            $this->filesystem->remove($this->tmpDir);
        } else {
            echo (sprintf("\nWARNING: Tests output not deleted: '%s'", $this->tmpDir));
        }

        parent::tearDown();
    }

    public function testBookPublish()
    {
        // if running in Travis-CI, PDF book publishing cannot be
        // tested because external utilities will be unavailable
        if ('true' === getenv('TRAVIS')) {
            $this->markTestSkipped("Detected Travis-CI build, skipping test");
            return;
        }
        
        $console = new ConsoleApplication($this->app);

        // find the test books
        $books = $this->app['finder']
            ->directories()
            ->name('pdf-test-book*')
            ->depth(0)
            ->in(__DIR__ . '/fixtures');

        foreach ($books as $book) {

            $slug = $book->getFileName();

            // mirror test book contents in temp dir
            $this->filesystem->mirror(
                __DIR__ . '/fixtures/' . $slug . '/input',
                $this->tmpDir . '/' . $slug
            );

            // look for and publish all the book editions
            $bookConfig = Yaml::parse($this->tmpDir . '/' . $slug . '/config.yml');
            $editionNames = array_keys($bookConfig['book']['editions']);

            foreach ($editionNames as $editionName) {
                // publish each book edition
                $input = new ArrayInput(
                    array(
                        'command' => 'publish',
                        'slug'    => $slug,
                        'edition' => $editionName,
                        '--dir'   => $this->tmpDir,
                    )
                );

                $output = $this->isDebug ? new ConsoleOutput() : new NullOutput();
                $console->find('publish')->run($input, $output);

                // assert that generated files are exactly the same as expected
                $generatedFiles = $this->app['finder']
                    ->files()
                    ->notName('.gitignore')
                    ->in($this->tmpDir . '/' . $slug . '/Output/' . $editionName);

                foreach ($generatedFiles as $file) {
                    if ('epub' == $file->getExtension()) {
                        // unzip both files to compare its contents
                        $workDir = $this->tmpDir . '/' . $slug . '/unzip/' . $editionName;
                        $generated = $workDir . '/generated';
                        $expected = $workDir . '/expected';

                        Toolkit::unzip($file->getRealPath(), $generated);
                        Toolkit::unzip(
                            __DIR__ . '/fixtures/' . $slug . '/expected/' .
                            $editionName . '/' . $file->getRelativePathname(),
                            $expected
                        );

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
                                    "ERROR on $book:\n '%s' file (into ZIP file '%s') not properly generated",
                                    $genFile->getRelativePathname(),
                                    $file->getPathName()
                                )
                            );
                        }

                        // assert that all required files are generated
                        $this->checkForMissingFiles($expected, $generated);
                        
                    } elseif ('pdf' == $file->getExtension()) {
                        // skip
                        if ($this->isDebug) {
                            echo sprintf("\nNOTICE: '%s' file has been generated. Please verify it manually.", $file->getPathname());
                            $this->needsManualVerification = true;
                        }
                    } else {
                        $this->assertFileEquals(
                            __DIR__ . '/fixtures/' . $slug . '/expected/' . $editionName . '/' . $file->getRelativePathname(
                            ),
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

                // assert than book publication took less than 5 seconds
                $this->assertLessThan(
                    5,
                    $this->app['app.timer.finish'] - $this->app['app.timer.start'],
                    sprintf("Publication of '%s' edition for '%s' book took more than 5 seconds", $editionName, $slug)
                );

                // reset app state before the next publishing
                $this->app = new Application();
                $this->app['console.output'] = new ConsoleOutput();

                $console = new ConsoleApplication($this->app);
            }
        }
    }

    /*
     * Assert that all expected files were generated
     */
    protected function checkForMissingFiles($dirExpected, $dirGenerated)
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
