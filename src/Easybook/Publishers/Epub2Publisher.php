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

use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;
use Easybook\Util\Toolkit;

class Epub2Publisher extends HtmlPublisher
{
    public function loadContents()
    {
        // 'toc' content type makes no sense in epub books
        // 'cover' is a very special content for epub books
        $excludedElements = array('cover', 'toc', 'lot', 'lof');

        // strip 'toc' and 'cover' elements before loading book contents
        $contents = array();
        foreach ($this->app->book('contents') as $content) {
            if (!in_array($content['element'], $excludedElements)) {
                $contents[] = $content;
            }
        }
        $this->app->book('contents', $contents);

        parent::loadContents();
    }

    public function decorateContents()
    {
        $decoratedItems = array();

        foreach ($this->app['publishing.items'] as $item) {
            $this->app->set('publishing.active_item', $item);

            // filter the original item content before decorating it
            $event = new BaseEvent($this->app);
            $this->app->dispatch(Events::PRE_DECORATE, $event);

            // Do nothing to decorate the item

            $event = new BaseEvent($this->app);
            $this->app->dispatch(Events::POST_DECORATE, $event);

            // get again 'item' object because POST_DECORATE event can modify it
            $decoratedItems[] = $this->app->get('publishing.active_item');
        }

        $this->app->set('publishing.items', $decoratedItems);
    }

    public function assembleBook()
    {
        // set the edition id needed for ebook generation
        $this->app->edition('id', $this->app['publishing.id']);

        // variables needed to hold the list of images and fonts of the book
        $bookImages = array();
        $bookFonts  = array();

        // prepare the temp directory used to build the book
        $bookTempDir = $this->app['app.dir.cache'].'/'.uniqid(
            $this->app['publishing.book.slug'].'-'.$this->app['publishing.edition'].'-'
        );

        $this->app->get('filesystem')->mkdir(array(
            $bookTempDir,
            $bookTempDir.'/book',
            $bookTempDir.'/book/META-INF',
            $bookTempDir.'/book/OEBPS',
            $bookTempDir.'/book/OEBPS/css',
            $bookTempDir.'/book/OEBPS/images',
            $bookTempDir.'/book/OEBPS/fonts',
        ));

        // generate easybook CSS file
        if ($this->app->edition('include_styles')) {
            $this->app->render('@theme/style.css.twig', array(
                    'resources_dir' => '..'
                ),
                $bookTempDir.'/book/OEBPS/css/easybook.css'
            );

            // copy book fonts and prepare font data for ebook manifest
            $this->app->get('filesystem')->copy(
                $this->app['app.dir.resources'].'/Fonts/Inconsolata/Inconsolata.ttf',
                $bookTempDir.'/book/OEBPS/fonts/Inconsolata.ttf'
            );
            $bookFonts[] = array(
                'id'        => 'font-1',
                'filePath'  => 'fonts/Inconsolata.ttf',
                'mediaType' => 'application/octet-stream'
            );
        }

        // generate custom CSS file
        $customCss = $this->app->getCustomTemplate('style.css');
        if (file_exists($customCss)) {
            $this->app->get('filesystem')->copy(
                $customCss,
                $bookTempDir.'/book/OEBPS/css/styles.css',
                true
            );
        }

        // each book element will generate an HTML page
        // use automatic slugs (chapter-1, chapter-2, ...) instead of
        // semantic slugs (lorem-ipsum, dolor-sit-amet, ...)
        $this->app->set('publishing.slugs', array());
        $items = array();
        foreach ($this->app['publishing.items'] as $item) {
            $pageName = array_key_exists('number', $item['config'])
                ? $item['config']['element'].' '.$item['config']['number']
                : $item['config']['element'];

            $slug = $this->app->get('slugger')->slugify(trim($pageName));

            $item['slug'] = $slug;
            // TODO: document this new item property
            $item['fileName'] = $slug.'.html';
            $items[] = $item;
        }
        // update `publishing items` with the new slug value
        $this->app->set('publishing.items', $items);

        // generate one HTML page for every book item
        $items = array();
        foreach ($this->app['publishing.items'] as $item) {
            $this->app->render('@theme/chunk.twig', array(
                    'item'           => $item,
                    'has_custom_css' => file_exists($customCss),
                ),
                $bookTempDir.'/book/OEBPS/'.$item['fileName']
            );
        }

        // copy book images and prepare image data for ebook manifest
        if (file_exists($imagesDir = $this->app['publishing.dir.contents'].'/images')) {
            $images = $this->app->get('finder')->files()->in($imagesDir);

            $i = 1;
            foreach ($images as $image) {
                $this->app->get('filesystem')->copy(
                    $image->getPathName(),
                    $bookTempDir.'/book/OEBPS/images/'.$image->getFileName()
                );

                $bookImages[] = array(
                    'id'        => 'figure-'.$i++,
                    'filePath'  => 'images/'.$image->getFileName(),
                    'mediaType' => 'image/'.pathinfo($image->getFilename(), PATHINFO_EXTENSION)
                );
            }
        }

        // look for cover images
        $cover = null;
        if (null != $image = $this->app->getCustomCoverImage()) {
            list($width, $height, $type) = getimagesize($image);
            $cover = array(
                'height'    => $height,
                'width'     => $width,
                'filePath'  => 'images/'.basename($image),
                'mediaType' => image_type_to_mime_type($type)
            );

            // copy the cover image
            $this->app->get('filesystem')->copy(
                $image,
                $bookTempDir.'/book/OEBPS/images/'.basename($image)
            );
        }

        // generate book cover
        $this->app->render('@theme/cover.twig', array(
                'cover' => $cover
            ),
            $bookTempDir.'/book/OEBPS/titlepage.html'
        );

        // generate OPF file
        $this->app->render('@theme/content.opf.twig', array(
                'cover'          => $cover,
                'has_custom_css' => file_exists($customCss),
                'fonts'          => $bookFonts,
                'images'         => $bookImages
            ),
            $bookTempDir.'/book/OEBPS/content.opf'
        );

        // generate NCX file
        $this->app->render('@theme/toc.ncx.twig', array(),
            $bookTempDir.'/book/OEBPS/toc.ncx'
        );

        // generate container.xml and mimetype files
        $this->app->render('@theme/container.xml.twig', array(),
            $bookTempDir.'/book/META-INF/container.xml'
        );
        $this->app->render('@theme/mimetype.twig', array(),
            $bookTempDir.'/book/mimetype'
        );

        // compress book contents as ZIP file and rename to .epub
        // TODO: the name of the book file (book.epub) must be configurable
        $this->zipBookContents($bookTempDir.'/book', $bookTempDir.'/book.zip');
        $this->app->get('filesystem')->copy(
            $bookTempDir.'/book.zip',
            $this->app['publishing.dir.output'].'/book.epub',
            true
        );

        // remove temp directory used to build the book
        $this->app->get('filesystem')->remove($bookTempDir);
    }

    /*
     * It creates the ZIP file of the .epub book contents.
     *
     * If the PHP ZIP extension isn't available, this command tries to generate
     * the ZIP file using the OS 'zip' command (this is useful for shared
     * hostings that disable ZIP extension).
     *
     * @param  string $directory  Book contents directory
     * @param  string $zip_file   The path of the generated ZIP file
     */
    private function zipBookContents($directory, $zip_file)
    {
        if (extension_loaded('zip')) {
            return Toolkit::zip($directory, $zip_file);
        }

        // After several hours trying to create ZIP files with lots of PHP
        // tools and libraries (Archive_Zip, Pclzip, zetacomponents/archive, ...)
        // I can't produce a proper ZIP file for ebook readers.
        // Therefore, if ZIP extension isn't enabled, the ePub ZIP file is
        // generated by executing 'zip' command

        // check if 'zip' command exists
        $process = new \Symfony\Component\Process\Process('zip');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                "[ERROR] You must enable the ZIP extension in PHP \n"
                ." or your system should be able to execute 'zip' console command."
            );
        }

        // To generate the ePub file, you must execute the following commands:
        //   $ cd /path/to/ebook/contents
        //   $ zip -X0 book.zip mimetype
        //   $ zip -rX9 book.zip * -x mimetype
        $command = sprintf(
            'cd %s && zip -X0 %s mimetype && zip -rX9 %s * -x mimetype',
            $directory, $zip_file, $zip_file
        );

        $process = new \Symfony\Component\Process\Process($command);
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
}
