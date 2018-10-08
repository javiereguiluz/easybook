<?php declare(strict_types=1);

namespace Easybook\Tests\Publisher\PdfPublisher;

use Easybook\Console\Command\PublishCommand;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\Toolkit;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

final class GeneralPdfPublisherTest extends AbstractContainerAwareTestCase
{
    /**
     * @var Toolkit
     */
    private $toolkit;

    protected function setUp(): void
    {
        $this->toolkit = $this->container->get(Toolkit::class);
    }

    public function testBookPublish(): void
    {
        // find the test books
        $books = $this->finder->directories()
            ->name('pdf-test-book*')
            ->depth(0)
            ->in(__DIR__ . '/fixtures')
            ->getIterator();

        foreach ($books as $book) {
            $slug = $book->getFileName();

            // mirror test book contents in temp dir
            $this->filesystem->mirror(__DIR__ . '/fixtures/' . $slug . '/input', $this->tmpDir . '/' . $slug);

            // look for and publish all the book editions
            // @todo: load config $this->tmpDir . '/' . $slug . '/config.yml');

            /** @var PublishCommand $publishCommand */
            $publishCommand = $this->container->get(PublishCommand::class);
            $publishCommand->run(new ArgvInput([]), new NullOutput());

            // assert that generated files are exactly the same as expected
            $generatedFiles = $this->finder
                ->files()
                ->notName('.gitignore')
                ->in($this->tmpDir . '/' . $slug . '/Output/' . $editionName)
                ->getIterator();

            foreach ($generatedFiles as $file) {
                if ($file->getExtension() === 'epub') {
                    // unzip both files to compare its contents
                    $workDir = $this->tmpDir . '/' . $slug . '/unzip/' . $editionName;
                    $generated = $workDir . '/generated';
                    $expected = $workDir . '/expected';

                    $this->toolkit->unzip($file->getRealPath(), $generated);
                    $this->toolkit->unzip(
                        __DIR__ . '/fixtures/' . $slug . '/expected/' .
                            $editionName . '/' . $file->getRelativePathname(),
                        $expected
                    );

                    // assert that generated files are exactly the same as expected
                    $genFiles = $this->finder
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
                } elseif ($file->getExtension() === 'pdf') {
                } else {
                    $this->assertFileEquals(
                        __DIR__ . '/fixtures/' . $slug . '/expected/' . $editionName . '/' . $file->getRelativePathname(
                        ),
                        $file->getPathname(),
                        sprintf("'%s' file not properly generated", $file->getPathname())
                    );
                }

                // assert that all required files are generated
                $this->checkForMissingFiles(
                    __DIR__ . '/fixtures/' . $slug . '/expected/' . $editionName,
                    $this->tmpDir . '/' . $slug . '/Output/' . $editionName
                );
            }
        }
    }

    /**
     * Assert that all expected files were generated
     */
    protected function checkForMissingFiles(string $dirExpected, string $dirGenerated): void
    {
        $expectedFiles = $this->finder
            ->files()
            ->notName('.gitignore')
            ->in($dirExpected)
            ->getIterator();

        foreach ($expectedFiles as $file) {
            $this->assertFileExists(
                $dirGenerated . '/' . $file->getRelativePathname(),
                sprintf("'%s' file has not been generated", $file->getPathname())
            );
        }
    }
}