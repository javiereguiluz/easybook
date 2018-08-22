<?php declare(strict_types=1);

namespace Easybook\Generator;

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

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
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
        $this->filesystem->mkdir([$this->bookDirectory . '/Contents/images', $this->bookDirectory . '/Output']);

        foreach (['chapter1.md', 'chapter2.md'] as $file) {
            $file = 'Contents/' . $file;
            $this->filesystem->copy($this->skeletonDirectory . '/' . $file, $this->bookDirectory . '/' . $file);
        }

        $this->filesystem->dumpFile($this->bookDirectory . '/config.yml', $this->render($this->skeletonDirectory, 'config.yml.twig', $this->configuration));
    }

    /**
     * @param mixed[] $parameters
     */
    private function render(string $skeletonDir, string $template, array $parameters): string
    {
        $loader = new Twig_Loader_Filesystem($skeletonDir);
        $twig = new Twig_Environment($loader, [
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ]);

        return $twig->render($template, $parameters);
    }
}
