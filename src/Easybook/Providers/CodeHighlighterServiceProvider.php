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
use Easybook\Util\CodeHighlighter;

class CodeHighlighterServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (empty($app['geshi'])) {
            $geshi = new GeshiServiceProvider();
            $geshi->register($app);
        }

        $app['highlighter'] = $app->share(function () use ($app) {
            return new CodeHighlighter($app);
        });
    }
}