<?php declare(strict_types=1);

namespace Easybook\Templating;

use Easybook\Book\Provider\BookProvider;
use Easybook\Book\Provider\CurrentEditionProvider;
use Easybook\Book\Provider\ImagesProvider;
use Easybook\Book\Provider\TablesProvider;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

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
     * @var CurrentEditionProvider
     */
    private $currentEditionProvider;

    /**
     * @var TablesProvider
     */
    private $tablesProvider;

    /**
     * @var ImagesProvider
     */
    private $imagesProvider;

    /**
     * @var string
     */
    private $themesDir;

    public function __construct(
        Filesystem $filesystem,
        Environment $twigEnvironment,
        BookProvider $bookProvider,
        CurrentEditionProvider $currentEditionProvider,
        TablesProvider $tablesProvider,
        ImagesProvider $imagesProvider,
        string $themesDir
    ) {
        $this->filesystem = $filesystem;
        $this->twigEnvironment = $twigEnvironment;
        $this->bookProvider = $bookProvider;
        $this->currentEditionProvider = $currentEditionProvider;
        $this->tablesProvider = $tablesProvider;
        $this->imagesProvider = $imagesProvider;
        $this->themesDir = $themesDir;
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

    private function beforeRender(string $template): void
    {
        /** @var ChainLoader $twigLoader */
        $twigLoader = $this->twigEnvironment->getLoader();

        $pathInfo = pathinfo($template);
        if ($pathInfo['extension'] !== 'twig') {
            // is not file name but a content - add to array loader
            $twigLoader->addLoader(new ArrayLoader([
                $template => $template,
            ]));
        } else {
            $themeFilesystemLoader = new FilesystemLoader();

            // Base theme (common styles per edition type)
            // <easybook>/app/Resources/Themes/Base/<edition-type>/Templates/<template-name>.twig
            $baseThemeDir = sprintf('%s/Base/%s/Templates', $this->themesDir, $this->currentEditionProvider->provide());
            $themeFilesystemLoader->addPath($baseThemeDir);
            $themeFilesystemLoader->addPath(
                $baseThemeDir,
                'theme'
            ); // use "@theme" reference in the code - pick just one
            $twigLoader->addLoader($themeFilesystemLoader);

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
        }

        $this->twigEnvironment->addGlobal('book', $this->bookProvider->provide());
        $this->twigEnvironment->addGlobal('edition', $this->currentEditionProvider->provide());

        // old one: publishing.list.images
        $this->twigEnvironment->addGlobal('all_images', $this->imagesProvider->getImages());
        // old one: publishing.list.tables
        $this->twigEnvironment->addGlobal('all_table', $this->tablesProvider->getTables());
    }
}
