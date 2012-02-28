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

    public function __construct($zipFile = 'easybook.zip')
    {
        $this->fileCount = 0;
        $this->filesystem = new Filesystem();
        $this->rootDir = realpath(__DIR__.'/../../../');

        $this->zipFile = $zipFile;
        if (file_exists($this->zipFile)) {
            unlink($this->zipFile);
        }

        // temp directory to copy the essential easybook files
        $this->packageDir = $this->rootDir.'/app/Cache/easybook';

        // delete the directory if it existed previously
        if (file_exists($this->packageDir)) {
            $this->filesystem->remove($this->packageDir);
        }
        $this->filesystem->mkdir($this->packageDir);

        // get easybook version
        $app = new \Easybook\DependencyInjection\Application();
        $this->version = $app['app.version'];
    }

    public function build()
    {
        echo sprintf("\nBuilding easybook %s package\n%s\n",
            $this->version, str_repeat('=', 80)
        );

        // add book script
        $this->addFile(new \SplFileInfo($this->rootDir.'/book'));

        // add autoloaders
        $this->addFile(new \SplFileInfo($this->rootDir.'/vendor/.composer/ClassLoader.php'));
        $this->addFile(new \SplFileInfo($this->rootDir.'/vendor/.composer/autoload.php'));
        $this->addFile(new \SplFileInfo($this->rootDir.'/vendor/.composer/autoload_namespaces.php'));
        $this->addFile(new \SplFileInfo($this->rootDir.'/app/autoload.php'));

        // add resources
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->notName('.DS_Store')
            ->in($this->rootDir.'/app/Resources')
        ;

        foreach ($finder as $file) {
            $this->addFile($file);
        }

        // add sample books
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

        // add core classes
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

        // add vendors
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

        // add license and Readme
        $this->addFile(new \SplFileInfo($this->rootDir.'/LICENSE.md'));
        $this->addFile(new \SplFileInfo($this->rootDir.'/README.md'));

        // compress all files into a single ZIP file
        Toolkit::zip($this->packageDir, './'.$this->zipFile);

        // delete temp directory
        $this->filesystem->remove($this->packageDir);

        echo sprintf("\n %d files added\n\n %s (%.2f MB) package built successfully\n\n",
            $this->fileCount, $this->zipFile, filesize($this->zipFile) / (1024*1024)
        );
    }

    private function addFile($file, $verbose = true)
    {
        $this->fileCount++;
        if ($verbose) { echo '.'; if (0 == $this->fileCount % 80) { echo "\n"; } }

        $relativePath = str_replace(
            dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR,
            '',
            $file->getRealPath()
        );

        $this->filesystem->copy($file->getRealPath(), $this->packageDir.'/'.$relativePath);
    }
}