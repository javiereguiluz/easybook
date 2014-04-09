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

use Easybook\DependencyInjection\Application;
use Easybook\DependencyInjection\ServiceProviderInterface;

class SluggerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['slugger.options'] = array(
            'separator' => '-',   // used between words and instead of illegal characters
            'prefix'    => '',    // prefix to be appended at the beginning of the slug
        );

        // stores all the generated slugs to ensure slug uniqueness
        $app['slugger.generated_slugs'] = array();

        $app['slugger'] = $app->share(function () use ($app) {
            if (function_exists('transliterator_transliterate')) {
                return new \Easybook\Utf8Slugger($app['slugger.options']['separator']);
            } else {
                return new \Easybook\Slugger($app['slugger.options']['separator']);
            }
        });
    }
}