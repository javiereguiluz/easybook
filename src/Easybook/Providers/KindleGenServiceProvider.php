<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Easybook\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class KindleGenServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['kindlegen.path'] = null;

        // the common installation dirs for KindleGen in several OS
        $app['kindlegen.default_paths'] = array(
            # Mac OS X & Linux
            '/usr/local/bin/kindlegen',
            '/usr/bin/kindlegen',
            # Windows
            'c:\KindleGen\kindlegen',
            'c:\KindleGen\kindlegen.exe',
        );

        // -c0: no compression
        // -c1: standard DOC compression
        // -c2: Kindle huffdic compression
        // -verbose: (even more) verbose output
        // -western: force Windows-1252 charset
        // -gif: transform book images to GIF
        $app['kindlegen.command_options'] = '-c1';
    }
}
