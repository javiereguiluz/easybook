<?php declare(strict_types=1);

namespace Easybook\Generator;

use Easybook\Templating\Renderer;
use Symfony\Component\Filesystem\Filesystem;

final class BookGenerator
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $bookDirectory;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var string
     */
    private $skeletonBookDirectory;

    public function __construct(Filesystem $filesystem, Renderer $renderer, string $skeletonBookDirectory)
    {
        $this->filesystem = $filesystem;
        $this->renderer = $renderer;
        $this->skeletonBookDirectory = $skeletonBookDirectory;
    }

    public function setBookDirectory(string $bookDirectory): void
    {
        // check if `$bookDir` directory is available
        // if not, create a unique directory name appending a numeric suffix
        $i = 1;
        $bookDir = $bookDirectory;
        while ($this->filesystem->exists($bookDirectory)) {
            $bookDirectory = $bookDir . '-' . $i++;
        }

        $this->bookDirectory = $bookDirectory;
    }

    public function getBookDirectory(): string
    {
        return $this->bookDirectory;
    }

    /**
     * Generates the hierarchy of files and directories needed to publish a book.
     */
    public function generate(): void
    {
        // why is this hardcoded? Finder?
        foreach (['chapter1.md', 'chapter2.md'] as $file) {
            $file = 'Contents/' . $file;
            $this->filesystem->copy($this->skeletonBookDirectory . '/' . $file, $this->bookDirectory . '/' . $file);
        }

        $this->renderer->renderToFile(
            $this->skeletonBookDirectory . '/config.yml.twig',
            [],
            $this->bookDirectory . '/config.yml'
        );
    }

    public function generateToDirectory(string $directory): void
    {
        $this->bookDirectory = $directory;
        $this->generate();
    }
}
