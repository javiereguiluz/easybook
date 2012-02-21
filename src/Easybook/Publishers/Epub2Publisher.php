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

use Easybook\Parsers\MdParser;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;
use Easybook\Util\Toolkit;

class Epub2Publisher extends HtmlPublisher
{
    public function loadContents()
    {
        // 'toc' content type makes no sense in epub books
        // strip 'toc' elements before loading book contents
        $contents = array();
        foreach ($this->app->book('contents') as $content) {
            if ('toc' != $content['element']) {
                $contents[] = $content;
            }
        }
        $this->app->book('contents', $contents);
        
        parent::loadContents();
    }
    
    public function decorateContents()
    {
        // Do nothing
    }
    
    public function assembleBook()
    {
        // set the edition id needed for ebook generation
        $this->app->edition('id', $this->app['publishing.id']);
        
        // prepare the temp directory used to build the book
        $bookTempDir = $this->app['app.dir.cache']
                       .'/'.$this->app['publishing.book.slug']
                       .'-'.$this->app['publishing.edition'];
        
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
            $this->app->render('style.css.twig', array(
                    'resources_dir' => $bookTempDir.'/book/OEBPS/'
                ),
                $bookTempDir.'/book/OEBPS/css/easybook.css'
            );
            
            // copy book fonts and prepare font data for ebook manifest
            $this->app->get('filesystem')->copy(
                $this->app['app.dir.resources'].'/Fonts/Inconsolata/Inconsolata.ttf',
                $bookTempDir.'/book/OEBPS/fonts/Inconsolata.ttf'
            );
            $fontsData[] = array(
                'id'        => 'font-1',
                'filePath'  => 'fonts/Inconsolata.ttf',
                'mediaType' => 'font/x-font-truetype'
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
            $this->app->render('chunk.twig', array(
                    'item'           => $item,
                    'has_custom_css' => file_exists($customCss),
                ),
                $bookTempDir.'/book/OEBPS/'.$item['fileName']
            );
        }
        
        // copy book images and prepare image data for ebook manifest
        $images = $this->app->get('finder')->files()->in(
            $this->app['publishing.dir.contents'].'/images'
        );
        
        $i = 1;
        foreach ($images as $image) {
            $this->app->get('filesystem')->copy(
                $image->getPathName(),
                $bookTempDir.'/book/OEBPS/images/'.$image->getFileName()
            );
            
            $imagesData[] = array(
                'id'        => 'figure-'.$i++,
                'filePath'  => 'images/'.$image->getFileName(),
                'mediaType' => 'image/'.pathinfo($image->getFilename(), PATHINFO_EXTENSION)
            );
        }
        
        // generate OPF file
        $this->app->render('content.opf.twig', array(
                'has_custom_css' => file_exists($customCss),
                'fonts'          => $fontsData  ?: array(),
                'images'         => $imagesData ?: array()
            ),
            $bookTempDir.'/book/OEBPS/content.opf'
        );
                
        // generate NCX file
        $this->app->render('toc.ncx.twig', array(),
            $bookTempDir.'/book/OEBPS/toc.ncx'
        );
        
        // generate container.xml and mimetype files
        $this->app->render('container.xml.twig', array(),
            $bookTempDir.'/book/META-INF/container.xml'
        );
        $this->app->render('mimetype.twig', array(),
            $bookTempDir.'/book/mimetype'
        );
        
        // compress book contents as ZIP file and rename to .epub
        // TODO: the name of the book file (book.epub) must be configurable
        Toolkit::zip($bookTempDir.'/book', $bookTempDir.'/book.zip');
        $this->app->get('filesystem')->copy(
            $bookTempDir.'/book.zip',
            $this->app['publishing.dir.output'].'/book.epub',
            true
        );
        
        // remove temp directory used to build the book
        $this->app->get('filesystem')->remove($bookTempDir);    
    }
}