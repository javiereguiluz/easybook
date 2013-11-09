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
use Easybook\Util\Toolkit;
use Easybook\Util\TwigCssExtension;

class TwigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['twig.options'] = array(
            'autoescape'       => false,
            // 'cache'         => $app['app.dir.cache'].'/Twig',
            'charset'          => $app['app.charset'],
            'debug'            => $app['app.debug'],
            'strict_variables' => $app['app.debug'],
        );

        $app['twig.loader'] = $app->share(function() use ($app) {
            $theme  = ucfirst($app->edition('theme'));
            $format = Toolkit::camelize($app->edition('format'), true);

            $loader = new \Twig_Loader_Filesystem($app['app.dir.themes']);

            // Base theme (common styles per edition type)
            // <easybook>/app/Resources/Themes/Base/<edition-type>/Templates/<template-name>.twig
            $baseThemeDir = sprintf('%s/Base/%s/Templates', $app['app.dir.themes'], $format);
            $loader->addPath($baseThemeDir);
            $loader->addPath($baseThemeDir, 'theme');
            $loader->addPath($baseThemeDir, 'theme_base');

            // Book theme (configured per edition in 'config.yml')
            // <easybook>/app/Resources/Themes/<theme>/<edition-type>/Templates/<template-name>.twig
            $bookThemeDir = sprintf('%s/%s/%s/Templates', $app['app.dir.themes'], $theme, $format);
            $loader->prependPath($bookThemeDir);
            $loader->prependPath($bookThemeDir, 'theme');

            $userTemplatePaths = array(
                // <book-dir>/Resources/Templates/<template-name>.twig
                $app['publishing.dir.templates'],
                // <book-dir>/Resources/Templates/<edition-type>/<template-name>.twig
                sprintf('%s/%s', $app['publishing.dir.templates'], strtolower($format)),
                // <book-dir>/Resources/Templates/<edition-name>/<template-name>.twig
                sprintf('%s/%s', $app['publishing.dir.templates'], $app['publishing.edition']),
            );

            foreach ($userTemplatePaths as $path) {
                if (file_exists($path)) {
                    $loader->prependPath($path);
                }
            }

            $defaultContentPaths = array(
                // <easybook>/app/Resources/Themes/Base/<edition-type>/Contents/<template-name>.twig
                sprintf('%s/Base/%s/Contents', $app['app.dir.themes'], $format),
                // <easybook>/app/Resources/Themes/<theme>/<edition-type>/Contents/<template-name>.twig
                sprintf('%s/%s/%s/Contents', $app['app.dir.themes'], $theme, $format),
            );

            foreach ($defaultContentPaths as $path) {
                if (file_exists($path)) {
                    $loader->prependPath($path, 'content');
                }
            }

            return $loader;
        });

        $app['twig'] = $app->share(function() use ($app) {
            $twig = new \Twig_Environment($app['twig.loader'], $app['twig.options']);
            $twig->addExtension(new TwigCssExtension());

            $twig->addGlobal('app', $app);

            if (null != $bookConfig = $app['publishing.book.config']) {
                $twig->addGlobal('book', $bookConfig['book']);

                $publishingEdition = $app['publishing.edition'];
                $editions = $app->book('editions');
                $twig->addGlobal('edition', $editions[$publishingEdition]);
            }

            return $twig;
        });
    }
}