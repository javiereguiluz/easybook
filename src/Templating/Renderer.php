<?php declare(strict_types=1);

namespace Easybook\Templating;

use Easybook\Book\Provider\BookProvider;
use Easybook\Book\Provider\EditionProvider;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

final class Renderer
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Environment
     */
    private $twigEnvironment;

    /**
     * @var BookProvider
     */
    private $bookProvider;

    /**
     * @var EditionProvider
     */
    private $editionProvider;

    public function __construct(
        Filesystem $filesystem,
        Environment $twigEnvironment,
        BookProvider $bookProvider,
        EditionProvider $editionProvider
    ) {
        $this->filesystem = $filesystem;
        $this->twigEnvironment = $twigEnvironment;
        $this->bookProvider = $bookProvider;
        $this->editionProvider = $editionProvider;
    }

    /**
     * @param mixed[] $variables
     */
    public function render(string $template, array $variables = []): string
    {
        $this->beforeRender($template);

        return $this->twigEnvironment->render($template, $variables);
    }

    /**
     * @param mixed[] $variables
     */
    public function renderToFile(string $template, array $variables, string $targetFile): void
    {
        $this->beforeRender($template);

        $rendered = $this->twigEnvironment->render($template, $variables);
        $this->filesystem->dumpFile($targetFile, $rendered);
    }

    private function ensureIsTwig(string $template): void
    {
        $templateSplFileInfo = new SplFileInfo($template);

        if ($templateSplFileInfo->getExtension() === 'twig') {
            return;
        }

        throw new RuntimeException(sprintf(
            'Unsupported format for "%s" template. Easybook only supports Twig, "%s" given.',
            $template,
            $templateSplFileInfo->getExtension()
        ));
    }

    private function beforeRender(string $template): void
    {
        $this->ensureIsTwig($template);

        // @todo use absolute paths instead of theme magic per edition

        $twigLoader = $this->twigEnvironment->getLoader();
        // required for internal template dependencies
        //        $twigLoaderFilesystem->addPath($baseThemeDir, 'theme_base');

        // Base theme (common styles per edition type)
        // <easybook>/app/Resources/Themes/Base/<edition-type>/Templates/<template-name>.twig
//        $baseThemeDir = sprintf('%s/Base/%s/Templates', $this->themesDir, $format);
//        $twigLoaderFilesystem->addPath($baseThemeDir);
//        $twigLoaderFilesystem->addPath($baseThemeDir, 'theme');

        // Book theme (configured per edition in 'config.yml')
        // <easybook>/app/Resources/Themes/<theme>/<edition-type>/Templates/<template-name>.twig
//        $bookThemeDir = sprintf('%s/%s/%s/Templates', $this->themesDir, $theme, $format);
//        $twigLoaderFilesystem->prependPath($bookThemeDir);
//        $twigLoaderFilesystem->prependPath($bookThemeDir, 'theme');

//        $userTemplatePaths = [
//            // <book-dir>/Resources/Templates/<template-name>.twig
//            $this->bookTemplatesDir,
//            // <book-dir>/Resources/Templates/<edition-type>/<template-name>.twig
//            sprintf('%s/%s', $this->bookTemplatesDir, strtolower($format)),
//            // <book-dir>/Resources/Templates/<edition-name>/<template-name>.twig
//            sprintf('%s/%s', $this->bookTemplatesDir, $app['publishing.edition']),
//        ];
//
//        foreach ($userTemplatePaths as $path) {
//            if (file_exists($path)) {
//                $twigLoaderFilesystem->prependPath($path);
//            }
//        }

//        $defaultContentPaths = [
//            // <easybook>/app/Resources/Themes/Base/<edition-type>/Contents/<template-name>.twig
//            sprintf('%s/Base/%s/Contents', $this->themesDir, $format),
//            // <easybook>/app/Resources/Themes/<theme>/<edition-type>/Contents/<template-name>.twig
//            sprintf('%s/%s/%s/Contents', $this->themesDir, $theme, $format),
//        ];

        $this->twigEnvironment->addGlobal('book', $this->bookProvider->provide());
        $this->twigEnvironment->addGlobal('edition', $this->editionProvider->provide());
    }
}
