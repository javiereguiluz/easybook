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
use Easybook\Parsers\MarkdownParser;

class ParserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['parser.options'] = array(
            // available syntaxes: 'original', 'php-markdown-extra', 'easybook'
            'markdown_syntax'  => 'easybook',
            // available types: 'markdown', 'fenced', 'github'
            'code_block_type'  => 'markdown',
        );

        $app['parser'] = $app->share(function ($app) {
            $format = strtolower($app['publishing.active_item']['config']['format']);

            if (in_array($format, array('md', 'mdown', 'markdown'))) {
                return new MarkdownParser($app);
            }

            throw new \RuntimeException(sprintf(
                'Unknown "%s" format for "%s" content (easybook only supports Markdown)',
                $format,
                $app['publishing.active_item']['config']['content']
            ));
        });
    }
}