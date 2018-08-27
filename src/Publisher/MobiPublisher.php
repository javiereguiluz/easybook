<?php declare(strict_types=1);

namespace Easybook\Publisher;

use Easybook\Exception\Publisher\RequiredBinFileNotFoundException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * It publishes the book as a MOBI file. All the internal links are transformed
 * into clickable cross-section book links.
 */
final class MobiPublisher extends Epub2Publisher
{
    /**
     * @var string
     */
    private $kindlegenPath;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * Kindle Publishing Guidelines rule that ebooks should contain an HTML TOC, so it cannot be
     *
     * @var string[]
     */
    private $excludedElements = ['cover', 'lot', 'lof'];

    public function __construct(?string $kindlegenPath, SymfonyStyle $symfonyStyle, Filesystem $filesystem)
    {
        $this->kindlegenPath = $kindlegenPath;
        $this->symfonyStyle = $symfonyStyle;
        $this->filesystem = $filesystem;
    }

    public function assembleBook(string $outputDirectory): void
    {
        $this->ensureKindlegenPathIsSet();

        parent::assembleBook();

        // depends on epub :)
        $epubFilePath = $outputDirectory . '/book.epub';

        $command = sprintf('%s %s -o book.mobi -c1', $outputDirectory, $epubFilePath);

        $process = new Process($command);
        $process->run();

        if ($process->isSuccessful()) {
            $this->symfonyStyle->note($process->getOutput());
        } else {
            throw new ProcessFailedException($process);
        }

        // remove the book.epub file used to generate the book.mobi file
        $this->filesystem->remove($epubFilePath);
    }

    private function ensureKindlegenPathIsSet(): void
    {
        if ($this->kindlegenPath === null) {
            throw new RequiredBinFileNotFoundException(sprintf(
                'Path to kindlegen that is required to create mobi files is empty. Did you set it in "parameters > kindlegen_path"?'
            ));
        }

        if (file_exists($this->kindlegenPath)) {
            return;
        }

        throw new RequiredBinFileNotFoundException(sprintf(
            'Kindlegen was not found in "%s" path provided in "parameters > kindlegen_path"',
            $this->kindlegenPath
        ));
    }
}
