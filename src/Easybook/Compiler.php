<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook;

use Symfony\Component\Finder\Finder;

/**
 * The Compiler class compiles easybook into a phar
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Compiler
{
    /**
     * Compiles easybook into a single phar file
     *
     * @param string $pharFile The full path to the file to create
     *
     * @throws \RuntimeException
     */
    public function compile($pharFile = 'easybook.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFile, 0, 'easybook');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        // Core classes
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('Tests')
            ->notName('Compiler.php')
            ->in(__DIR__.'/..');

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        // Vendors
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->in(__DIR__.'/../../vendor/');

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        // Resources
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->in(__DIR__.'/../../app/Resources');

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        // Autoload
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/.composer/ClassLoader.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/.composer/autoload.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/.composer/autoload_namespaces.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../app/autoload.php'));

        // Bin
        $content = file_get_contents(__DIR__.'/../../book');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('book', $content);

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        $phar->compressFiles(\Phar::GZ);

        unset($phar);
    }

    private function addFile($phar, $file, $strip = true)
    {
        $path = str_replace(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR, '', $file->getRealPath());

        if ($strip) {
            $content = php_strip_whitespace($file);
        } else {
            $content = "\n".file_get_contents($file)."\n";
        }

        $phar->addFromString($path, $content);
    }

    private function getStub()
    {
        return <<<'EOF'
#!/usr/bin/env php
<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Phar::mapPhar('easybook');

require 'phar://easybook/book';

__HALT_COMPILER();
EOF;
    }
}
