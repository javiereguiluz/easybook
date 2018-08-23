<?php declare(strict_types=1);

namespace Easybook\Publishers;

/**
 * It publishes the book as a single HTML page. All the internal links
 * are transformed into anchors. This means that the generated book can be
 * browsed offline or copied under any web server directory.
 */
final class HtmlPublisher extends AbstractPublisher
{
    /**
     * @var bool
     */
    private $bookEditionIncludeStyles;

    public function __construct(bool $bookEditionIncludeStyles)
    {
        $this->bookEditionIncludeStyles = $bookEditionIncludeStyles;
    }

    public function assembleBook(): void
    {
        // generate easybook CSS file
        if ($this->bookEditionIncludeStyles) {
            $this->renderer->renderToFile(
                '@theme/style.css.twig',
                ['resources_dir' => $this->app['app.dir.resources'] . '/'],
                $this->app['publishing.dir.output'] . '/css/easybook.css'
            );
        }

        // generate custom CSS file
        $customCss = $this->app->getCustomTemplate('style.css');
        $hasCustomCss = file_exists($customCss);
        if ($hasCustomCss) {
            $this->filesystem->copy($customCss, $this->app['publishing.dir.output'] . '/css/styles.css', true);
        }

        // implode all the contents to create the whole book
        $this->renderer->renderToFile(
            'book.twig',
            [
                'items' => $this->app['publishing.items'],
                'has_custom_css' => $hasCustomCss,
            ],
            $this->app['publishing.dir.output'] . '/book.html'
        );

        // copy book images
        $imagesDir = $this->app['publishing.dir.contents'] . '/images';
        if (file_exists($imagesDir)) {
            $this->filesystem->mirror($imagesDir, $this->app['publishing.dir.output'] . '/images');
        }
    }

    public function getFormat(): string
    {
        return 'html';
    }
}
