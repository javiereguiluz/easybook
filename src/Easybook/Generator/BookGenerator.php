<?php

namespace Easybook\Generator;

use Symfony\Component\Filesystem\Filesystem;

class BookGenerator extends Generator
{
    private $filesystem;
    private $bookDirectory;
    private $skeletonDirectory;
    private $configuration;

    /**
     * Sets the configuration variables used to render the config.yml template.
     *
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Sets the Filesystem instance.
     *
     * @param Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Sets the skeletons directory.
     *
     * @param string $skeletonDirectory
     */
    public function setSkeletonDirectory($skeletonDirectory)
    {
        $this->skeletonDirectory = $skeletonDirectory;
    }

    /**
     * Sets the book directory.
     *
     * @param string $bookDirectory
     */
    public function setBookDirectory($bookDirectory)
    {
        // check if `$bookDir` directory is available
        // if not, create a unique directory name appending a numeric suffix
        $i = 1;
        $bookDir = $bookDirectory;
        while ($this->filesystem->exists($bookDirectory)) {
            $bookDirectory = $bookDir.'-'.$i++;
        }

        $this->bookDirectory = $bookDirectory;
    }

    /**
     * Returns the book directory.
     *
     * @return string The book directory
     */
    public function getBookDirectory()
    {
        return $this->bookDirectory;
    }

    /**
     * Generates the hierarchy of files and directories needed
     * to publish a book.
     */
    public function generate()
    {
        $this->filesystem->mkdir(array(
            $this->bookDirectory.'/Contents/images',
            $this->bookDirectory.'/Output'
        ));

        foreach(array('chapter1.md', 'chapter2.md') as $file) {
            $file = 'Contents/'.$file;
            $this->filesystem->copy(
                $this->skeletonDirectory.'/'.$file,
                $this->bookDirectory.'/'.$file
            );
        }

        $this->renderFile(
            $this->skeletonDirectory,
            'config.yml.twig',
            $this->bookDirectory.'/config.yml',
            $this->configuration
        );
    }
}