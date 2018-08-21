<?php declare(strict_types=1);

namespace Easybook\Generator;

use Symfony\Component\Filesystem\Filesystem;

final class BookGenerator extends Generator
{
    private $filesystem;

    private $bookDirectory;

    private $skeletonDirectory;

    private $configuration;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Sets the configuration variables used to render the config.yml template.
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * Sets the skeletons directory.
     */
    public function setSkeletonDirectory(string $skeletonDirectory): void
    {
        $this->skeletonDirectory = $skeletonDirectory;
    }

    /**
     * Sets the book directory.
     */
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

    /**
     * Returns the book directory.
     *
     * @return string The book directory
     */
    public function getBookDirectory(): string
    {
        return $this->bookDirectory;
    }

    /**
     * Generates the hierarchy of files and directories needed
     * to publish a book.
     */
    public function generate(): void
    {
        $this->filesystem->mkdir([
            $this->bookDirectory . '/Contents/images',
            $this->bookDirectory . '/Output',
        ]);

        foreach (['chapter1.md', 'chapter2.md'] as $file) {
            $file = 'Contents/' . $file;
            $this->filesystem->copy($this->skeletonDirectory . '/' . $file, $this->bookDirectory . '/' . $file);
        }

        $this->renderFile(
            $this->skeletonDirectory,
            'config.yml.twig',
            $this->bookDirectory . '/config.yml',
            $this->configuration
        );
    }
}
