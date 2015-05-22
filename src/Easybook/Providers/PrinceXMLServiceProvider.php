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
use Easybook\Util\Prince;

class PrinceXMLServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['prince.path'] = null;

        // the common installation dirs for PrinceXML in several OS
        $app['prince.default_paths'] = array(
            '/usr/local/bin/prince',                         # Mac OS X
            '/usr/bin/prince',                               # Linux
            'C:\Program Files\Prince\engine\bin\prince.exe'  # Windows
        );

        $app['prince'] = function () use ($app) {
            $princePath = $app['prince.path'] ?: $app->findPrinceXmlExecutable();
            // ask the user about the location of the executable
            if (null == $princePath) {
                $princePath = $app->askForPrinceXMLExecutablePath();

                if (!file_exists($princePath)) {
                    throw new \RuntimeException(sprintf(
                         "We couldn't find the PrinceXML executable in the given directory (%s)", $princePath
                    ));
                }
            }

            $prince = new Prince($princePath);
            $prince->setHtml(true);

            return $prince;
        };
    }
}