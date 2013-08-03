<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 * (c) Matthieu Moquet <matthieu@moquet.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Util;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Easybook\DependencyInjection\Application;

/**
 * Compresses easybook essential files into a single ZIP file
 */
class Compressor
{
    private $fileCount;
    private $filesystem;
    private $rootDir;
    private $packageDir;
    private $zipFile;
    private $version;

    public function __construct()
    {
        $this->fileCount = 0;
        $this->filesystem = new Filesystem();
        $this->rootDir = realpath(__DIR__.'/../../../');

        // needed to get easybook version
        $app = new Application();
        $this->version = $app->getVersion();

        // temp directory to copy the essential easybook files
        $this->packageDir = $this->rootDir.'/app/Cache/easybook';

        // delete the directory if it existed previously
        if (file_exists($this->packageDir)) {
            $this->filesystem->remove($this->packageDir);
        }
        $this->filesystem->mkdir($this->packageDir);
    }

    public function build($zipFile = null)
    {
        $this->zipFile = $zipFile ?: sprintf("%s/easybook-%s.zip", $this->rootDir, $this->version);
        if (file_exists($this->zipFile)) {
            unlink($this->zipFile);
        }

        // add package files
        $this->addBookScript();
        $this->addAutoloaders();
        $this->addResources();
        $this->addSampleBooks();
        $this->addCommandHelp();
        $this->addCoreClasses();
        $this->addVendors();
        $this->addLicenseAndReadme();

        // compress all files into a single ZIP file
        Toolkit::zip($this->packageDir, $this->zipFile);

        // delete temp directory
        $this->filesystem->remove($this->packageDir);

        echo sprintf("\n %d files added\n\n %s (%.2f MB) package built successfully\n\n",
            $this->fileCount, $this->zipFile, filesize($this->zipFile) / (1024*1024)
        );
    }

    private function addBookScript()
    {
        $this->addFile(new \SplFileInfo($this->rootDir.'/book'));
    }

    private function addAutoloaders()
    {
        $this->addFile(new \SplFileInfo($this->rootDir.'/vendor/autoload.php'));
        $this->addFile(new \SplFileInfo($this->rootDir.'/vendor/composer/ClassLoader.php'));
        $this->addFile(new \SplFileInfo($this->rootDir.'/vendor/composer/autoload_classmap.php'));
        $this->addFile(new \SplFileInfo($this->rootDir.'/vendor/composer/autoload_namespaces.php'));
    }

    private function addResources()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->notName('.DS_Store')
            ->in($this->rootDir.'/app/Resources')
        ;

        foreach ($finder as $file) {
            $this->addFile($file);
        }
    }

    private function addSampleBooks()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->notName('.DS_Store')
            ->exclude('Output')
            ->exclude('Resources')
            ->in(array(
                $this->rootDir.'/doc/easybook-doc-en',
                $this->rootDir.'/doc/easybook-doc-es'
            ))
        ;

        foreach ($finder as $file) {
            $this->addFile($file);
        }
    }

    private function addCommandHelp()
    {
        $this->addFile(new \SplFileInfo(
            $this->rootDir.'/src/Easybook/Console/Command/Resources/BookNewCommandHelp.txt'
        ));
        $this->addFile(new \SplFileInfo(
            $this->rootDir.'/src/Easybook/Console/Command/Resources/BookPublishCommandHelp.txt'
        ));
    }

    private function addCoreClasses()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('.DS_Store')
            ->exclude('Tests')
            ->notName('Builder.php')
            ->in($this->rootDir.'/src')
        ;

        foreach ($finder as $file) {
            $this->addFile($file);
        }
    }

    private function addVendors()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->notName('.DS_Store')
            ->notName('README*')
            ->notName('CHANGELOG')
            ->notName('AUTHORS')
            ->notName('create_pear_package.php')
            ->notName('composer.json')
            ->notName('installed.json')
            ->notName('package.xml.tpl')
            ->notName('phpunit.xml.dist')
            ->exclude(array(
                'docs',
                'tests',
                'twig/bin',
                'twig/doc',
                'twig/ext',
                'twig/test'
            ))
            ->in($this->rootDir.'/vendor')
        ;

        foreach ($finder as $file) {
            $this->addFile($file);
        }
    }

    private function addLicenseAndReadme()
    {
        $this->addFile(new \SplFileInfo($this->rootDir.'/LICENSE.md'));
        $this->addFile(new \SplFileInfo($this->rootDir.'/README.md'));
    }

    /**
     * It adds the given file to the final ZIP package.
     *
     * @param \SplFileInfo $file The file to be added
     * @param bool $verbose      If true, it shows progress by printing a dot for each added file
     */
    private function addFile(\SplFileInfo $file, $verbose = true)
    {
        $this->fileCount++;

        if ($verbose) {
            echo '.';
            if (0 == $this->fileCount % 80) {
                echo "\n";
            }
        }

        $relativePath = str_replace(
            dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR,
            '',
            $file->getRealPath()
        );

        $this->filesystem->copy($file->getRealPath(), $this->packageDir.'/'.$relativePath);
    }
}
