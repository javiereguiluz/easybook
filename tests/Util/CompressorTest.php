<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Util;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Easybook\Util\Compressor;

final class CompressorTest extends AbstractContainerAwareTestCase
{
    public function testCompressAndPackageEasybook()
    {
        $compressor = $this->container->get(Compressor::class);


        // setup temp dir for generated ZIP file
        $tmpDir = $app['app.dir.cache'].'/'.uniqid('phpunit_', true);
        $filesystem = new Filesystem();
        $filesystem->mkdir($tmpDir);

        ob_start();
        $compressor->build($tmpDir.'/easybook-package.zip');
        $commandOutput = ob_get_contents();
        ob_end_clean();

        $this->assertRegExp('/\d{3} files added/', $commandOutput);
        $this->assertRegExp('/easybook-package.zip \(.* MB\) package built successfully/', $commandOutput);

        // tear down the temp directories
        $filesystem->remove($tmpDir);
    }
}
