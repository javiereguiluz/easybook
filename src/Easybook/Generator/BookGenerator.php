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
     * @var string
     */
    private $skeletonDirectory;

    /**
     * @var mixed[]
     */
    private $configuration = [];
    /**
     * @var Renderer
     */
    private $renderer;

    public function __construct(Filesystem $filesystem, Renderer $renderer)
    {
        $this->filesystem = $filesystem;
        $this->renderer = $renderer;
    }

    /**
     * @param mixed[] $configuration
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function setSkeletonDirectory(string $skeletonDirectory): void
    {
        $this->skeletonDirectory = $skeletonDirectory;
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
            $this->filesystem->copy($this->skeletonDirectory . '/' . $file, $this->bookDirectory . '/' . $file);
        }

        $this->renderer->renderToFile(
            $this->skeletonDirectory . '/config.yml.twig',
            $this->configuration,
            $this->bookDirectory . '/config.yml'
        );
    }
}
