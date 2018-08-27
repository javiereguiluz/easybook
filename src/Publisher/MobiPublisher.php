<?php declare(strict_types=1);

namespace Easybook\Publisher;

use Easybook\Exception\Publisher\RequiredBinFileNotFoundException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * It publishes the book as a MOBI file. All the internal links are transformed
 * into clickable cross-section book links.
 */
final class MobiPublisher extends AbstractPublisher
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
     * Kindle Publishing Guidelines rule that ebooks should contain an HTML TOC, so it cannot be excluded.
     *
     * @var string[]
     */
    private $excludedElements = ['cover', 'lot', 'lof'];

    /**
     * @var string[]
     */
    private $possibleKindlegenPaths = [
        # Mac OS X & Linux
        '/usr/local/bin/kindlegen',
        '/usr/bin/kindlegen',
        # Windows
        'c:\KindleGen\kindlegen',
        'c:\KindleGen\kindlegen.exe',
    ];

    /**
     * @var Epub2Publisher
     */
    private $epub2Publisher;

    public function __construct(?string $kindlegenPath, SymfonyStyle $symfonyStyle, Epub2Publisher $epub2Publisher)
    {
        $this->kindlegenPath = $kindlegenPath;
        $this->symfonyStyle = $symfonyStyle;
        $this->epub2Publisher = $epub2Publisher;
    }

    public function assembleBook(string $outputDirectory): string
    {
        $this->ensureExistingKindlegenPathIsSet();

        // depends on epub :)
        $epubFilePath = $this->epub2Publisher->publishBook($outputDirectory);

        $command = sprintf('%s %s -o book.mobi -c1', $outputDirectory, $epubFilePath);

        $process = new Process($command);
        $process->run();

        if ($process->isSuccessful()) {
            $this->symfonyStyle->note($process->getOutput());
        } else {
            throw new ProcessFailedException($process);
        }

        return $outputDirectory . '/book.mobi';
    }

    public function getFormat(): string
    {
        return 'mobi';
    }

    private function ensureExistingKindlegenPathIsSet(): void
    {
        if ($this->kindlegenPath === null) {
            foreach ($this->possibleKindlegenPaths as $possibleKindlegenPath) {
                if (file_exists($possibleKindlegenPath)) {
                    $this->kindlegenPath = $possibleKindlegenPath;
                    return;
                }
            }

            throw new RequiredBinFileNotFoundException(sprintf(
                'Kindlegen is required to create mobi. The path to is empty though. We also looked into "%s" but did not find it. Set it in "parameters > kindlegen_path".',
                implode('", "', $this->possibleKindlegenPaths)
            ));
        }

        if (file_exists($this->kindlegenPath)) {
            return;
        }

        throw new RequiredBinFileNotFoundException(sprintf(
            'Kindlegen was not found in "%s" path provided in "parameters > kindlegen_path".',
            $this->kindlegenPath
        ));
    }
}
