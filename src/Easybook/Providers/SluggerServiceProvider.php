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
use EasySlugger\Slugger;
use EasySlugger\Utf8Slugger;

class SluggerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['slugger.options'] = array(
            'separator' => '-', // used between words and instead of illegal characters
            'prefix' => '',     // prefix to be appended at the beginning of the slug
        );

        // stores all the generated slugs to ensure slug uniqueness
        $app['slugger.generated_slugs'] = array();

        $app['slugger'] = function () use ($app) {
            if (PHP_VERSION_ID > 50400) {
                return new Utf8Slugger($app['slugger.options']['separator']);
            } else {
                return new Slugger($app['slugger.options']['separator']);
            }
        };
    }
}
