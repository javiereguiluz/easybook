<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Publishers;

use Symfony\Component\Process\Process;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;
use Easybook\Util\Toolkit;

/**
 * It publishes the book as an EPUB file. All the internal links are transformed
 * into clickable cross-section book links.
 */
class Epub2Publisher extends HtmlPublisher
{
    // 'toc' content type usually makes no sense in epub books (see below)
    // 'cover' is a very special content for epub books
    protected $excludedElements = array('cover', 'lot', 'lof', 'toc');

    public function loadContents()
    {
        // strip excluded elements before loading book contents
        $contents = array();
        foreach ($this->app->book('contents') as $content) {
            if (!in_array($content['element'], $this->excludedElements)) {
                $contents[] = $content;
            }
        }
        $this->app->book('contents', $contents);

        parent::loadContents();
    }

    /**
     * Overrides the base publisher method to avoid the decoration of the book items.
     * Instead of using the regular Twig templates based on the item type (e.g. chapter),
     * ePub books items are decorated afterwards with some special Twig templates.
     */
    public function decorateContents()
    {
        $decoratedItems = array();

        foreach ($this->app['publishing.items'] as $item) {
            $this->app['publishing.active_item'] = $item;

            // filter the original item content before decorating it
            $event = new BaseEvent($this->app);
            $this->app->dispatch(Events::PRE_DECORATE, $event);

            // Do nothing to decorate the item

            $event = new BaseEvent($this->app);
            $this->app->dispatch(Events::POST_DECORATE, $event);

            // get again 'item' object because POST_DECORATE event can modify it
            $decoratedItems[] = $this->app['publishing.active_item'];
        }

        $this->app['publishing.items'] = $decoratedItems;
    }

    public function assembleBook()
    {
        $bookTmpDir = $this->prepareBookTemporaryDirectory();

        // generate easybook CSS file
        if ($this->app->edition('include_styles')) {
            $this->app->render(
                '@theme/style.css.twig',
                array('resources_dir' => '..'),
                $bookTmpDir.'/book/OEBPS/css/easybook.css'
            );
        }

        // generate custom CSS file
        $customCss = $this->app->getCustomTemplate('style.css');
        $hasCustomCss = file_exists($customCss);
        if ($hasCustomCss) {
            $this->app['filesystem']->copy(
                $customCss,
                $bookTmpDir.'/book/OEBPS/css/styles.css',
                true
            );
        }

        $bookItems = $this->normalizePageNames($this->app['publishing.items']);
        $this->app['publishing.items'] = $bookItems;

        // generate one HTML page for every book item
        foreach ($bookItems as $item) {
            $renderedTemplatePath = $bookTmpDir.'/book/OEBPS/'.$item['page_name'].'.html';
            $templateVariables = array(
                'item'           => $item,
                'has_custom_css' => $hasCustomCss,
            );

            // try first to render the specific template for each content
            // type, if it exists (e.g. toc.twig, chapter.twig, etc.) and
            // use chunk.twig as the fallback template
            try {
                $templateName = $item['config']['element'].'.twig';

                $this->app->render($templateName, $templateVariables, $renderedTemplatePath);
            } catch (\Twig_Error_Loader $e) {
                $this->app->render('chunk.twig', $templateVariables, $renderedTemplatePath);
            }
        }

        $bookImages = $this->prepareBookImages($bookTmpDir.'/book/OEBPS/images');
        $bookCover  = $this->prepareBookCoverImage($bookTmpDir.'/book/OEBPS/images');
        $bookFonts  = $this->prepareBookFonts($bookTmpDir.'/book/OEBPS/fonts');

        // generate the book cover page
        $this->app->render('cover.twig', array('customCoverImage' => $bookCover),
            $bookTmpDir.'/book/OEBPS/titlepage.html'
        );

        // generate the OPF file (the ebook manifest)
        $this->app->render('content.opf.twig', array(
                'cover'          => $bookCover,
                'has_custom_css' => $hasCustomCss,
                'fonts'          => $bookFonts,
                'images'         => $bookImages,
                'items'          => $bookItems
            ),
            $bookTmpDir.'/book/OEBPS/content.opf'
        );

        // generate the NCX file (the table of contents)
        $this->app->render('toc.ncx.twig', array('items' => $bookItems),
            $bookTmpDir.'/book/OEBPS/toc.ncx'
        );
        
        // generate container.xml and mimetype files
        $this->app->render('container.xml.twig', array(),
            $bookTmpDir.'/book/META-INF/container.xml'
        );
        $this->app->render('mimetype.twig', array(),
            $bookTmpDir.'/book/mimetype'
        );

        $this->fixInternalLinks($bookTmpDir.'/book/OEBPS');

        // compress book contents as ZIP file and rename to .epub
        $this->zipBookContents($bookTmpDir.'/book', $bookTmpDir.'/book.zip');
        $this->app['filesystem']->copy(
            $bookTmpDir.'/book.zip',
            $this->app['publishing.dir.output'].'/book.epub',
            true
        );

        // remove temp directory used to build the book
        $this->app['filesystem']->remove($bookTmpDir);
    }

    /**
     * Prepares the temporary directory where the book contents are generated
     * before packing them into the resulting EPUB file. It also creates the
     * full directory structure required for EPUB books.
     *
     * @return string The absolute path of the directory created.
     */
    private function prepareBookTemporaryDirectory()
    {
        $bookDir = $this->app['app.dir.cache'].'/'
                   .uniqid($this->app['publishing.book.slug']);

        $this->app['filesystem']->mkdir(array(
            $bookDir,
            $bookDir.'/book',
            $bookDir.'/book/META-INF',
            $bookDir.'/book/OEBPS',
            $bookDir.'/book/OEBPS/css',
            $bookDir.'/book/OEBPS/images',
            $bookDir.'/book/OEBPS/fonts',
        ));

        return $bookDir;
    }

    /**
     * It prepares the book images by copying them into the appropriate
     * temporary directory. It also prepares an array with all the images
     * data needed later to generate the full ebook contents manifest.
     *
     * @param  string $targetDir The directory where the images are copied.
     *
     * @return array             Images data needed to create the book manifest.
     * @throws \RuntimeException If the $targetDir doesn't exist.
     */
    private function prepareBookImages($targetDir)
    {
        if (!file_exists($targetDir)) {
            throw new \RuntimeException(sprintf(
                " ERROR: Books images couldn't be copied because \n"
                ." the given '%s' \n"
                ." directory doesn't exist.",
                $targetDir
            ));
        }

        $imagesDir = $this->app['publishing.dir.contents'].'/images';
        $imagesData = array();

        if (file_exists($imagesDir)) {
            $images = $this->app['finder']->files()->in($imagesDir);

            $i = 1;
            foreach ($images as $image) {
                $this->app['filesystem']->copy(
                    $image->getPathName(),
                    $targetDir.'/'.$image->getFileName()
                );

                $imagesData[] = array(
                    'id'        => 'figure-'.$i++,
                    'filePath'  => 'images/'.$image->getFileName(),
                    'mediaType' => 'image/'.pathinfo($image->getFilename(), PATHINFO_EXTENSION)
                );
            }
        }

        return $imagesData;
    }

    /**
     * It prepares the book cover image (if the book defines one).
     *
     * @param  string $targetDir The directory where the cover image is copied.
     *
     * @return array|null        Book cover image data or null if the book doesn't
     *                           include a cover image.
     */
    private function prepareBookCoverImage($targetDir)
    {
        $cover = null;

        if (null != $image = $this->app->getCustomCoverImage()) {
            list($width, $height, $type) = getimagesize($image);

            $cover = array(
                'height'    => $height,
                'width'     => $width,
                'filePath'  => 'images/'.basename($image),
                'mediaType' => image_type_to_mime_type($type)
            );

            $this->app['filesystem']->copy($image, $targetDir.'/'.basename($image));
        }

        return $cover;
    }

    /**
     * It prepares the book fonts by copying them into the appropriate
     * temporary directory. It also prepares an array with all the font
     * data needed later to generate the full ebook contents manifest.
     *
     * For now, epub books only include the Inconsolata font to display
     * their code listings.
     *
     * @param  string $targetDir The directory where the fonts are copied.
     *
     * @return array             Font data needed to create the book manifest.
     */
    private function prepareBookFonts($targetDir)
    {
        if (!file_exists($targetDir)) {
            throw new \RuntimeException(sprintf(
                " ERROR: Books fonts couldn't be copied because \n"
                    ." the given '%s' \n"
                    ." directory doesn't exist.",
                $targetDir
            ));
        }

        $fontsDir = $this->app['app.dir.resources'].'/Fonts/Inconsolata';
        $fontsData = array();

        if (file_exists($fontsDir)) {
            $fonts = $this->app['finder']->files()->name('*.ttf')->in($fontsDir);

            $i = 1;
            foreach ($fonts as $font) {
                $this->app['filesystem']->copy(
                    $font->getPathName(),
                    $targetDir.'/'.$font->getFileName()
                );

                $fontsData[] = array(
                    'id'        => 'font-'.$i++,
                    'filePath'  => 'fonts/'.$font->getFileName(),
                    'mediaType' => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $font->getPathName())
                );
            }
        }

        return $fontsData;
    }

    /**
     * The generated HTML pages aren't named after the items' original slugs
     * (e.g. introduction-to-lorem-ipsum.html) but using their content types
     * and numbers (e.g. chapter-1.html).
     *
     * This method creates a new property for each item called 'page_name' which
     * stores the normalized page name that should have this chunk.
     *
     * @param  array $items The original book items.
     *
     * @return array        The book items with their new 'page_name' property.
     */
    private function normalizePageNames($items)
    {
        $itemsWithNormalizedPageNames = array();

        foreach ($items as $item) {
            $itemPageName = isset($item['config']['number'])
                ? $item['config']['element'].' '.$item['config']['number']
                : $item['config']['element'];

            $item['page_name'] = $this->app->slugifyUniquely($itemPageName);

            $itemsWithNormalizedPageNames[] = $item;
        }

        return $itemsWithNormalizedPageNames;
    }

    /*
     * It creates the ZIP file of the .epub book contents.
     *
     * According to the "International Digital Publishing Forum" validator,
     * a valid ePub file must have "an uncompressed Mimetype entry at the first
     * element of the compressed archive".
     *
     * Unfortunately, the PHP Zip extension doesn't allow to set the compression
     * level for ZIP files. See: https://bugs.php.net/bug.php?id=41243
     * This means that it's impossible to create a valid ePub file using the
     * PHP Zip extension.
     *
     * This method tries to use the OS 'zip' command first and if it's not
     * available, it fallbacks to the Zip extension, which will inevitably
     * generate an invalid ePub file.
     *
     * @param  string $directory  Book contents directory
     * @param  string $zip_file   The path of the generated ZIP file
     */
    private function zipBookContents($directory, $zip_file)
    {
        // check if the operating system supports the 'zip' command
        $process = new Process('zip');
        $process->run();

        if ($process->isSuccessful()) {
            return $this->zipBookContentsNatively($directory, $zip_file);
        }

        // fallback to the 'zip' PHP extension if the 'zip' command is not available
        if (extension_loaded('zip')) {
            return $this->zipBookContentsWithPhpExtension($directory, $zip_file);
        }

        throw new \RuntimeException(
            "[ERROR] The ePub file couldn't be published because your \n"
            ." Operating System doesn't support the 'zip' command and your \n"
            ." PHP installation hasn't enabled the 'Zip' extension. \n\n"
            ." Please, enable the 'zip' extension and publish the book again."
        );
    }

    /**
     * It generates the compressed file required for the ePub book.
     * To compress the contents, it uses the 'zip' command of the
     * Operating System to execute the following:
     *
     *   $ cd /path/to/ebook/contents
     *   $ zip -X0 book.zip mimetype
     *   $ zip -rX9 book.zip * -x mimetype
     *
     * @param  string $directory  Book contents directory
     * @param  string $zip_file   The path of the generated ZIP file
     */
    private function zipBookContentsNatively($directory, $zip_file)
    {
        $command = sprintf(
            'cd %s && zip -X0 %s mimetype && zip -rX9 %s * -x mimetype',
            $directory, $zip_file, $zip_file
        );

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                "[ERROR] 'zip' command execution wasn't successful.\n\n"
                ."Executed command:\n"
                ." $command\n\n"
                ."Result:\n"
                .$process->getErrorOutput()
            );
        }
    }

    /**
     * It generates the compressed file required for the ePub book.
     * To compress the contents, it uses the 'Zip' PHP extension.
     *
     * @param  string $directory  Book contents directory
     * @param  string $zip_file   The path of the generated ZIP file
     */
    private function zipBookContentsWithPhpExtension($directory, $zip_file)
    {
        Toolkit::zip($directory, $zip_file);
    }

    /**
     * If fixes the internal links of the book (the links that point to chapters
     * and sections of the book).
     *
     * The author of the book always uses relative links, such as:
     *   see <a href="#new-content-types">this section</a> for more information
     *
     * In order to work, the relative URIs must be replaced by absolute URIs:
     *   see <a href="chapter3/page-slug.html#new-content-types">this section</a>
     *
     * Unlike books published as websites, the absolute URIs of the ePub books
     * cannot start with './' or '../' In other words, ./chapter.html and
     * ./chapter.html#section-slug are wrong and chapter.html or
     * chapter.html#section-slug are right.
     *
     * @param  string $chunksDir The directory where the book's HTML page/chunks
     *                           are stored
     */
    private function fixInternalLinks($chunksDir)
    {
        $generatedChunks = $this->app['finder']->files()->name('*.html')->in($chunksDir);

        // maps the original internal links (e.g. #new-content-types)
        // with the correct absolute URL needed for a website
        // (e.g. chapter-3/advanced-features.html#new-content-types
        $internalLinkMapper = array();

        //look for the ID of every book section
        foreach ($generatedChunks as $chunk) {
            $htmlContent = file_get_contents($chunk->getPathname());

            $matches = array();
            $numHeadings = preg_match_all(
                '/<h[1-6].*id="(?<id>.*)".*<\/h[1-6]>/U',
                $htmlContent, $matches, PREG_SET_ORDER
            );

            if ($numHeadings > 0) {
                foreach ($matches as $match) {
                    $relativeUri = '#'.$match['id'];
                    $absoluteUri = $chunk->getRelativePathname().$relativeUri;

                    $internalLinkMapper[$relativeUri] = $absoluteUri;
                }
            }
        }

        // replace the internal relative URIs for the mapped absolute URIs
        foreach ($generatedChunks as $chunk) {
            $htmlContent = file_get_contents($chunk->getPathname());

            $htmlContent = preg_replace_callback(
                '/<a href="(?<uri>#.*)"(.*)<\/a>/Us',
                function ($matches) use ($internalLinkMapper) {
                    if (isset($internalLinkMapper[$matches['uri']])) {
                        $newUri = $internalLinkMapper[$matches['uri']];
                    } else {
                        $newUri = $matches['uri'];
                    }

                    return sprintf('<a class="internal" href="%s"%s</a>', $newUri, $matches[2]);
                },
                $htmlContent
            );

            file_put_contents($chunk->getPathname(), $htmlContent);
        }
    }
}
