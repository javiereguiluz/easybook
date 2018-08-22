<?php declare(strict_types=1);

namespace Easybook\Twig;

use Easybook\Util\Toolkit;
use Twig_Loader_Filesystem;

final class TwigLoaderFactory
{
    public function create(): Twig_Loader_Filesystem
    {
        $theme = ucfirst($app->edition('theme'));
        $format = Toolkit::camelize($app->edition('format'), true);

        $loader = new Twig_Loader_Filesystem($app['app.dir.themes']);

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

        $userTemplatePaths = [
            // <book-dir>/Resources/Templates/<template-name>.twig
            $app['publishing.dir.templates'],
            // <book-dir>/Resources/Templates/<edition-type>/<template-name>.twig
            sprintf('%s/%s', $app['publishing.dir.templates'], strtolower($format)),
            // <book-dir>/Resources/Templates/<edition-name>/<template-name>.twig
            sprintf('%s/%s', $app['publishing.dir.templates'], $app['publishing.edition']),
        ];

        foreach ($userTemplatePaths as $path) {
            if (file_exists($path)) {
                $loader->prependPath($path);
            }
        }

        $defaultContentPaths = [
            // <easybook>/app/Resources/Themes/Base/<edition-type>/Contents/<template-name>.twig
            sprintf('%s/Base/%s/Contents', $app['app.dir.themes'], $format),
            // <easybook>/app/Resources/Themes/<theme>/<edition-type>/Contents/<template-name>.twig
            sprintf('%s/%s/%s/Contents', $app['app.dir.themes'], $theme, $format),
        ];

        foreach ($defaultContentPaths as $path) {
            if (file_exists($path)) {
                $loader->prependPath($path, 'content');
            }
        }

        return $loader;
    }
}
