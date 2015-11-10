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

class WkhtmltopdfServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['wkhtmltopdf.path'] = null;

        // the common installation dirs for wkhtmltopdf in several OS
        $app['wkhtmltopdf.default_paths'] = array(
            '/usr/local/bin/wkhtmltopdf',                         # Mac OS X
            '/usr/bin/wkhtmltopdf',                               # Linux
            'C:\Program Files\wkhtmltopdf.exe',                   # Windows TODO: write the actual path 
        );

        $app['wkhtmltopdf'] = function () use ($app) {
            $wkhtmltopdfPath = $app['wkhtmltopdf.path'] ?: $app->findWkhtmltopdfExecutable();
            // ask the user about the location of the executable
            if (null === $wkhtmltopdfPath) {
                $wkhtmltopdfPath = $app->findWkhtmltopdfExecutable();

                if (!file_exists($wkhtmltopdfPath)) {
                    throw new \RuntimeException(sprintf(
                         "We couldn't find the wkhtmltopdf executable in the given directory (%s)", $wkhtmltopdfPath
                    ));
                }
            }

            $wkhtmltopdf = new Wkhtmltopdf($wkhtmltopdfPath);
            $wkhtmltopdf->setHtml(true);

            return $wkhtmltopdf;
        };
    }
}
