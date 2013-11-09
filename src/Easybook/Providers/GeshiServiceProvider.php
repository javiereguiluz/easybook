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

class GeshiServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['geshi'] = function () use ($app) {
            $geshi = new \GeSHi();
            $geshi->enable_classes(); // this must be the first method (see Geshi doc)
            $geshi->set_encoding($app['app.charset']);
            $geshi->enable_line_numbers(GESHI_NO_LINE_NUMBERS);
            $geshi->enable_keyword_links(false);

            return $geshi;
        };
    }
}