<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\DependencyInjection;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

use Easybook\Publishers\PdfPublisher;
use Easybook\Publishers\HtmlPublisher;
use Easybook\Publishers\HtmlChunkedPublisher;
use Easybook\Publishers\Epub2Publisher;
use Easybook\Parsers\MdParser;
use Easybook\Util\Prince;
use Easybook\Util\Slugger;
use Easybook\Util\Toolkit;

class Application extends \Pimple
{
    public function __construct()
    {
        $app = $this;

        // -- global generic parameters ---------------------------------------
        $this['app.debug']     = true;
        $this['app.charset']   = 'UTF-8';
        $this['app.name']      = 'easybook';
        $this['app.version']   = '4.2';
        $this['app.signature'] = "\n"
        ."                     |              |    \n"
        ." ,---.,---.,---.,   .|---.,---.,---.|__/ \n"
        ." |---',---|`---.|   ||   ||   ||   ||  \ \n"
        ." `---'`---^`---'`---|`---'`---'`---'`   `\n"
        ."                `---'\n";

        // -- global directories location -------------------------------------
        $this['app.dir.base']               = realpath(__DIR__.'/../../../');
        $this['app.dir.cache']              = $this['app.dir.base'].'/app/Cache';
        $this['app.dir.doc']                = $this['app.dir.base'].'/doc';
        $this['app.dir.resources']          = $this['app.dir.base'].'/app/Resources';
        $this['app.dir.plugins']            = $this['app.dir.base'].'/src/Easybook/Plugins';
        $this['app.dir.translations']       = $this['app.dir.resources'].'/Translations';
        $this['app.dir.skeletons']          = $this['app.dir.resources'].'/Skeletons';

        $this['app.dir.theme_pdf']          = $this['app.dir.resources'].'/Themes/Pdf';
        $this['app.dir.theme_html']         = $this['app.dir.resources'].'/Themes/Html';
        $this['app.dir.theme_html_chunked'] = $this['app.dir.resources'].'/Themes/HtmlChunked';
        $this['app.dir.theme_epub']         = $this['app.dir.resources'].'/Themes/Epub2';
        $this['app.dir.theme_epub2']        = $this['app.dir.resources'].'/Themes/Epub2';
        //$this['app.dir.theme_epub3'] = $this['app.dir.resources'].'/Themes/Epub3';

        // -- timer -----------------------------------------------------------
        $this['app.timer.start']  = 0.0;
        $this['app.timer.finish'] = 0.0;

        // -- publishing process variables ------------------------------------
        $this['publishing.dir.app_theme'] = '';  # holds the app theme dir for the current edition
        $this['publishing.dir.book']      = '';
        $this['publishing.dir.contents']  = '';
        $this['publishing.dir.resources'] = '';
        $this['publishing.dir.plugins']   = '';
        $this['publishing.dir.templates'] = '';
        $this['publishing.dir.output']    = '';
        $this['publishing.edition']       = '';
        $this['publishing.items']         = array();
        // TODO: think about 'parsing_item' name. Change to 'current_item', 'active_item'?
        $this['publishing.parsing_item']  = array();
        $this['publishing.book.slug']     = '';
        $this['publishing.book.items']    = array();
        $this['publishing.slugs']         = array(); // Holds all the slugs generated, to avoid repetitions
        $this['publishing.list.images']   = array();
        $this['publishing.list.tables']   = array();
        
        $this['publishing.id'] = $this->share(function ($app) {
            if (null != $isbn = $app->edition('isbn')) {
                return array('scheme' => 'isbn', 'value' => $isbn);
            }
            
            // if the book doesn't declare an ISBN, generate
            // a unique ID based on RFC 4211 UUID v4
            return array('scheme' => 'URN', 'value' => Toolkit::uuid());
        });
        
        // -- event dispatcher ------------------------------------------------
        $this['dispatcher'] = $this->share(function () {
            return new EventDispatcher();
        });

        // -- finder ----------------------------------------------------------
        $this['finder'] = function () {
            return new Finder();
        };

        // -- filesystem ------------------------------------------------------
        $this['filesystem'] = $this->share(function ($app) {
            return new Filesystem();
        });

        // -- publisher -------------------------------------------------------
        $this['publisher'] = $this->share(function ($app) {
            $outputFormat = $app->edition('format');

            switch (strtolower($outputFormat)) {
                case 'pdf':
                    return new PdfPublisher($app);
                
                case 'html':
                    return new HtmlPublisher($app);
                
                case 'html_chunked':
                    return new HtmlChunkedPublisher($app);
                
                case 'epub':
                case 'epub2':
                    return new Epub2Publisher($app);
                
                //case 'epub3':
                //    return new Epub3Publisher($app);
                
                default:
                    throw new \Exception(sprintf(
                        'Unknown "%s" format for "%s" edition (allowed: "pdf", "html", "html_chunked", "epub", "epub2")',
                        $outputFormat,
                        $app->get('publishing.edition')
                    ));
            }
        });

        // -- parser ----------------------------------------------------------
        $this['parser'] = $this->share(function ($app) {
            $format = strtolower($app['publishing.parsing_item']['config']['format']);

            // TODO: extensibility -> support several format parsers (RST, Textile, ...)
            switch ($format) {
                case 'md':
                case 'mdown':
                case 'markdown':
                    return new MdParser($app);
                default:
                    throw new \Exception(sprintf(
                        'Unknown "%s" format for "%s" content (easybook only supports Markdown)',
                        $format,
                        $app['publishing.parsing_item']['config']['content']
                    ));
            }
        });

        // -- twig ------------------------------------------------------------
        $this['twig.options'] = array(
            'autoescape'       => false,
            // 'cache'            => $app['app.dir.cache'].'/Twig,
            'charset'          => $this['app.charset'],
            'debug'            => $this['app.debug'],
            'strict_variables' => $this['app.debug'],
        );

        $this['twig.path'] = '';  // custom template path (rarely used/needed)

        $this['twig'] = function() use ($app) {
            $twig = new \Twig_Environment($app['twig.loader'], $app['twig.options']);
            
            $twig->addGlobal('app', $app);
            
            if (null != $app->get('book')) {
                $twig->addGlobal('book', $app->get('book'));
            
                $publishingEdition = $app->get('publishing.edition');
                $editions = $app->book('editions');
                $twig->addGlobal('edition', $editions[$publishingEdition]);
            }
            
            return $twig;
        };

        $this['twig.loader'] = function() use ($app) {
            $paths = array();
            
            // path directly passed to container
            if ('' != $app['twig.path'] and null != $app['twig.path']) {
                $paths[] = $app['twig.path'];
            }
            
            // custom templates for this edition
            // <book-dir>/Resources/Templates/<edition-name>/<template-name>.twig
            $customEditionTemplatesDirectory =
                $app->get('publishing.dir.templates').'/'.$app['publishing.edition'];
            if (file_exists($customEditionTemplatesDirectory)) {
                $paths[] = $customEditionTemplatesDirectory;
            }

            // custom templates for the edition type (pdf, html, html_chunked)
            // <book-dir>/Resources/Templates/<edition-type>/<template-name>.twig
            $customEditionTypeTemplatesDirectory =
                $app->get('publishing.dir.templates').'/'.$app->edition('format');
            if (file_exists($customEditionTypeTemplatesDirectory)) {
                $paths[] = $customEditionTypeTemplatesDirectory;
            }

            // custom templates for the book (same templates for all editions)
            // <book-dir>/Resources/Templates/<template-name>.twig
            $customBookTemplatesDirectory = $app->get('publishing.dir.templates');
            if (file_exists($customBookTemplatesDirectory)) {
                $paths[] = $customBookTemplatesDirectory;
            }
            
            // generic easybook templates
            $paths[] = $app['publishing.dir.app_theme'].'/Templates';

            return new \Twig_Loader_Filesystem($paths);
        };

        // -- princeXML -------------------------------------------------------
        $this['prince.default_paths'] = array(
            '/usr/local/bin/prince',                         # Mac OS X
            '/usr/bin/prince',                               # Linux
            'C:\Program Files\Prince\engine\bin\prince.exe'  # Windows
        );

        $this['prince'] = $app->share(function () use($app) {
            // look for the executable file of PrinceXML
            $princePath = null;
            foreach ($app['prince.default_paths'] as $path) {
                if (file_exists($path)) {
                    $princePath = $path;
                    break;
                }
            }

            if (null == $princePath) {
                echo sprintf(" In order to generate PDF files, PrinceXML library must be installed. \n\n"
                    ." We couldn't find PrinceXML executable in any of the following directories: \n"
                    ."   -> %s \n\n"
                    ." If you haven't installed it yet, you can download a fully-functional demo at: \n"
                    ." %s \n\n"
                    ." If you have installed in a custom directory, please type its full absolute path:\n > ",
                    implode($app['prince.default_paths'], "\n   -> "),
                    'http://www.princexml.com/download'
                );

                $input = trim(fgets(STDIN));

                if (file_exists($input)) {
                    $princePath = $input;
                    echo "\n";
                }
                else {
                    throw new \Exception(sprintf(
                        "We couldn't find the PrinceXML executable in the given directory (%s)", $input
                    ));
                }
            }

            $prince = new Prince($princePath);
            $prince->setHtml(true);

            return $prince;
        });

        // -- slugger ---------------------------------------------------------
        $this['slugger'] = $app->share(function () use ($app) {
            return new Slugger($app);
        });

        // -- code syntax highlighter -----------------------------------------
        $this['geshi'] = function () use ($app) {
            require_once __DIR__.'/../../../vendor/geshi/geshi/geshi.php';

            $geshi = new \GeSHi();
            $geshi->enable_classes(); // this must be the first method (see Geshi doc)
            $geshi->set_encoding($app['app.charset']);
            $geshi->enable_line_numbers(GESHI_NO_LINE_NUMBERS);
            $geshi->enable_keyword_links(false);

            return $geshi;
        };

        // -- labels ---------------------------------------------------------
        $this['labels'] = $app->share(function () use ($app) {
            $labels = Yaml::parse(
                $app['app.dir.translations'].'/labels.'.$app->book('language').'.yml'
            );
            
            // books can define their own labels files
            if (null != $customLabelsFile = $app->getCustomLabelsFile()) {
                $customLabels = Yaml::parse($customLabelsFile);
                return Toolkit::array_deep_merge($labels, $customLabels);
            }
            
            return $labels;
        });

        // -- titles ----------------------------------------------------------
        $this['titles'] = $app->share(function () use ($app) {
            $titles = Yaml::parse(
                $app['app.dir.translations'].'/titles.'.$app->book('language').'.yml'
            );
            
            // books can define their own titles files
            if (null != $customTitlesFile = $app->getCustomTitlesFile()) {
                $customTitles = Yaml::parse($customTitlesFile);
                return Toolkit::array_deep_merge($titles, $customTitles);
            }
            
            return $titles;
        });
    }
    
    public function get($id)
    {
        return $this->offsetGet($id);
    }
    
    public function set($id, $value)
    {
        $this->offsetSet($id, $value);
    }

    public function append($id, $value)
    {
        $array = $this->get($id);
        $array[] = $value;
        $this->set($id, $array);

        return $array;
    }

    public function extendEdition($parent)
    {
        $book    = $this->get('book');
        $edition = $this->get('publishing.edition');

        if (!array_key_exists($parent, $book['editions'])) {
            throw new \UnexpectedValueException(sprintf(
                " ERROR: '%s' edition extends nonexistent '%s' edition"
                ."\n\n"
                ."Check in '%s' file \n"
                ."that the value of 'extends' option in '%s' edition is a valid \n"
                ."edition of the book",
                $edition, $parent, realpath($this['publishing.dir.book'].'/config.yml'), $edition
            ));
        }

        $editionConfig = $book['editions'][$edition];
        $parentConfig  = $book['editions'][$parent];
        $config = array_merge($parentConfig, $editionConfig);
        
        $book['editions'][$edition] = $config;
        $this->set('book', $book);
    }

    public function getLabel($element, $variables = array())
    {
        $label = array_key_exists($element, $this['labels']['label'])
            ? $this['labels']['label'][$element]
            : '';

        // some elements (mostly chapters and appendices) have a different label for each level (h1, ..., h6)
        if (is_array($label)) {
            $label = $label[$variables['item']['level']-1];
        }
        
        return $this->renderString($label, $variables);
    }

    public function getTitle($element)
    {
        return array_key_exists($element, $this['titles']['title'])
            ? $this['titles']['title'][$element]
            : '';
    }

    public function renderString($string, $variables)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_String(), $this->get('twig.options'));

        return $twig->render($string, $variables);
    }

    // copied from http://github.com/sensio/SensioGeneratorBundle/blob/master/Generator/Generator.php
    public function renderFile($originDir, $template, $target, $parameters)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        $twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem($originDir),
            $this->get('twig.options')
        );
        file_put_contents($target, $twig->render($template, $parameters));
    }

    /*
     * Shortcut method to render templates
     */
    public function render($template, $variables = array(), $target = null)
    {
        if ('.twig' == substr($template, -5)) {
            $rendered = $this->get('twig')->render($template, $variables);
            
            if (null != $target) {
                file_put_contents($target, $rendered);
            }
            
            return $rendered;
        }
        else {
            throw new \Exception(sprintf(
                'Unsupported "%s" template format (easybook only supports Twig)',
                substr($template, -5)
            ));
        }
    }

    /*
     * If the book overrides the given templateName, this method returns the path
     * of the custom template. The search order is:
     *
     *   1. <book>/Resources/Templates/<edition-name>/<templateName>
     *   2. <book>/Resources/Templates/<edition-format>/<templateName>
     *   3. <book>/Resources/Templates/<templateName>
     *
     * It returns null if there is no custom template for $templateName.
     */
    public function getCustomTemplate($templateName)
    {
        $paths = array(
            $this['publishing.dir.templates'].'/'.$this['publishing.edition'],
            $this['publishing.dir.templates'].'/'.$this->edition('format'),
            $this['publishing.dir.templates']
        );

        return $this->getCustomFile($templateName, $paths);
    }
    
    /*
     * It looks for custom book labels. The search order is:
     *   1. <book>/Resources/Translations/<edition-name>/labels.<book-language>.yml
     *   2. <book>/Resources/Translations/<edition-format>/labels.<book-language>.yml
     *   3. <book>/Resources/Translations/labels.<book-language>.yml
     *
     * It returns null if there is no custom labels for the book.
     */
    public function getCustomLabelsFile()
    {
        $labelsFileName = 'labels.'.$this->book('language').'.yml';
        $paths = array(
            $this['publishing.dir.resources'].'/Translations/'.$this['publishing.edition'],
            $this['publishing.dir.resources'].'/Translations/'.$this->edition('format'),
            $this['publishing.dir.resources'].'/Translations'
        );
        
        return $this->getCustomFile($labelsFileName, $paths);
    }
    
    /*
     * It looks for custom book titles. The search order is:
     *   1. <book>/Resources/Translations/<edition-name>/titles.<book-language>.yml
     *   2. <book>/Resources/Translations/<edition-format>/titles.<book-language>.yml
     *   3. <book>/Resources/Translations/titles.<book-language>.yml
     *
     * It returns null if there is no custom labels for the book.
     */
    public function getCustomTitlesFile()
    {
        $titlesFileName = 'titles.'.$this->book('language').'.yml';
        $paths = array(
            $this['publishing.dir.resources'].'/Translations/'.$this['publishing.edition'],
            $this['publishing.dir.resources'].'/Translations/'.$this->edition('format'),
            $this['publishing.dir.resources'].'/Translations'
        );
        
        return $this->getCustomFile($titlesFileName, $paths);
    }

    /*
     * It looks for custom book cover images. The search order is:
     *   1. <book>/Resources/Templates/<edition-name>/cover.jpg
     *   2. <book>/Resources/Templates/<edition-format>/cover.jpg
     *   3. <book>/Resources/Templates/<templateName>/cover.jpg
     *
     * It returns null if there is no custom cover image.
     */
    public function getCustomCoverImage()
    {
        $coverFileName = 'cover.jpg';
        $paths = array(
            $this['publishing.dir.templates'].'/'.$this['publishing.edition'],
            $this['publishing.dir.templates'].'/'.$this->edition('format'),
            $this['publishing.dir.templates']
        );

        return $this->getCustomFile($coverFileName, $paths);
    }

    /*
     * Looks for the existance of $fileName inside the paths defined in $paths array.
     * It returns null if the file doesn't exist in any of the paths
     */
    public function getCustomFile($file, $paths)
    {
        foreach ($paths as $path) {
            if (file_exists($path.'/'.$file)) {
                return $path.'/'.$file;
            }
        }
        
        return null;
    }

    public function highlight($code, $language = 'code')
    {
        $geshi = $this->get('geshi');

        $geshi->set_source($code);
        $geshi->set_language($language);

        return $geshi->parse_code();
    }

    /*
     * Shortcut method to dispatch events
     */
    public function dispatch($eventName, $eventObject = null)
    {
        $this->get('dispatcher')->dispatch($eventName, $eventObject);
    }
    
    /*
     * Shortcut to get/set book configuration
     */
    public function book($key, $value = null)
    {
        if (null == $value) {
            $book = $this->get('book');
            return $book[$key];
        }
        else {
            $book = $this->get('book');
            $book[$key] = $value;
            $this->set('book', $book);
        }
    }
    
    /*
     * Shortcut to get/set book publishing edition configuration
     */
    public function edition($key, $value = null)
    {
        if (null == $value) {
            $publishingEdition = $this->get('publishing.edition');
            $editions = $this->book('editions');
            return array_key_exists($key, $editions[$publishingEdition])
                   ? $editions[$publishingEdition][$key]
                   : null;
        }
        else {
            $book = $this->get('book');
            $publishingEdition = $this->get('publishing.edition');
            $book['editions'][$publishingEdition][$key] = $value;
            $this->set('book', $book);
        }
    }
}
