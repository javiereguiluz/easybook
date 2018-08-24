<?php declare(strict_types=1);

namespace Easybook\Twig;

use Easybook\Util\Toolkit;
use Twig_Loader_Filesystem;

final class TwigLoaderFactory
{
    /**
     * @var Toolkit
     */
    private $toolkit;

    /**
     * @var string
     */
    private $themesDir;

    /**
     * @var string
     */
    private $bookTemplatesDir;

    public function __construct(Toolkit $toolkit, string $themesDir, string $bookTemplatesDir)
    {
        $this->toolkit = $toolkit;
        $this->themesDir = $themesDir;
        $this->bookTemplatesDir = $bookTemplatesDir;
    }

    public function create(): Twig_Loader_Filesystem
    {
        $theme = ucfirst($app->edition('theme'));
        $format = $this->toolkit->camelize($app->edition('format'));

        $twigLoaderFilesystem = new Twig_Loader_Filesystem($this->themesDir);

        // Base theme (common styles per edition type)
        // <easybook>/app/Resources/Themes/Base/<edition-type>/Templates/<template-name>.twig
        $baseThemeDir = sprintf('%s/Base/%s/Templates', $this->themesDir, $format);
        $twigLoaderFilesystem->addPath($baseThemeDir);
        $twigLoaderFilesystem->addPath($baseThemeDir, 'theme');
        $twigLoaderFilesystem->addPath($baseThemeDir, 'theme_base');

        // Book theme (configured per edition in 'config.yml')
        // <easybook>/app/Resources/Themes/<theme>/<edition-type>/Templates/<template-name>.twig
        $bookThemeDir = sprintf('%s/%s/%s/Templates', $this->themesDir, $theme, $format);
        $twigLoaderFilesystem->prependPath($bookThemeDir);
        $twigLoaderFilesystem->prependPath($bookThemeDir, 'theme');

        $userTemplatePaths = [
            // <book-dir>/Resources/Templates/<template-name>.twig
            $this->bookTemplatesDir,
            // <book-dir>/Resources/Templates/<edition-type>/<template-name>.twig
            sprintf('%s/%s', $this->bookTemplatesDir, strtolower($format)),
            // <book-dir>/Resources/Templates/<edition-name>/<template-name>.twig
            sprintf('%s/%s', $this->bookTemplatesDir, $app['publishing.edition']),
        ];

        foreach ($userTemplatePaths as $path) {
            if (file_exists($path)) {
                $twigLoaderFilesystem->prependPath($path);
            }
        }

        $defaultContentPaths = [
            // <easybook>/app/Resources/Themes/Base/<edition-type>/Contents/<template-name>.twig
            sprintf('%s/Base/%s/Contents', $this->themesDir, $format),
            // <easybook>/app/Resources/Themes/<theme>/<edition-type>/Contents/<template-name>.twig
            sprintf('%s/%s/%s/Contents', $this->themesDir, $theme, $format),
        ];

        foreach ($defaultContentPaths as $path) {
            if (file_exists($path)) {
                $twigLoaderFilesystem->prependPath($path, 'content');
            }
        }

        return $twigLoaderFilesystem;
    }
}
