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
use Easybook\Util\CodeHighlighter;

class CodeHighlighterServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        if (empty($app['geshi'])) {
            $geshi = new GeshiServiceProvider();
            $geshi->register($app);
        }

        $app['highlighter'] = function () use ($app) {
            return new CodeHighlighter($app);
        };
    }
}