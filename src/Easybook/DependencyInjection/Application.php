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
use Easybook\Parsers\MarkdownParser;
use Easybook\Util\Configurator;
use Easybook\Util\Prince;
use Easybook\Util\Slugger;
use Easybook\Util\Toolkit;
use Easybook\Util\TwigCssExtension;
use Easybook\Util\Validator;

class Application extends \Pimple
{
    const VERSION = '4.8';

    public function __construct()
    {
        $app = $this;

        // -- global generic parameters ---------------------------------------
        $this['app.debug']     = false;
        $this['app.charset']   = 'UTF-8';
        $this['app.name']      = 'easybook';
        $this['app.signature'] = "\n"
        ."                     |              |    \n"
        ." ,---.,---.,---.,   .|---.,---.,---.|__/ \n"
        ." |---',---|`---.|   ||   ||   ||   ||  \ \n"
        ." `---'`---^`---'`---|`---'`---'`---'`   `\n"
        ."                `---'\n";

        // -- global directories location -------------------------------------
        $this['app.dir.base']         = realpath(__DIR__.'/../../../');
        $this['app.dir.cache']        = $this['app.dir.base'].'/app/Cache';
        $this['app.dir.doc']          = $this['app.dir.base'].'/doc';
        $this['app.dir.resources']    = $this['app.dir.base'].'/app/Resources';
        $this['app.dir.plugins']      = $this['app.dir.base'].'/src/Easybook/Plugins';
        $this['app.dir.translations'] = $this['app.dir.resources'].'/Translations';
        $this['app.dir.skeletons']    = $this['app.dir.resources'].'/Skeletons';
        $this['app.dir.themes']       = $this['app.dir.resources'].'/Themes';

        // -- default book options -------------------------------------------
        $this['app.book.defaults'] = array(
            'title'            => '"Change this: Book title"',
            'author'           => '"Change this: Author Name"',
            'edition'          => 'First edition',
            'language'         => 'en',
            'publication_date' => null,
            'generator'        => array(
                'name'         => $this['app.name'],
                'version'      => $this->getVersion(),
            ),
            'contents'         => array(),
            'editions'         => array()
        );

        // -- default edition options -----------------------------------------
        // TODO: should each edition type define different default values?
        $this['app.edition.defaults'] = array(
            'format'          => 'html',
            'highlight_cache' => false,
            'highlight_code'  => false,
            'include_styles'  => true,
            'isbn'            => null,
            'labels'          => array('appendix', 'chapter', 'figure'),
            'margin'          => array(
                'top'         => '25mm',
                'bottom'      => '25mm',
                'inner'       => '30mm',
                'outter'      => '20mm'
            ),
            'page_size'       => 'A4',
            'theme'           => 'clean',
            'toc'             => array(
                'deep'        => 2,
                'elements'    => array('appendix', 'chapter')
            ),
            'two_sided'       => true
        );

        // -- console ---------------------------------------------------------
        $this['console.input']  = null;
        $this['console.output'] = null;
        $this['console.dialog'] = null;

        // -- timer -----------------------------------------------------------
        $this['app.timer.start']  = 0.0;
        $this['app.timer.finish'] = 0.0;

        // -- publishing process variables ------------------------------------
        // holds the app theme dir for the current edition
        $this['publishing.dir.app_theme']   = '';
        $this['publishing.dir.book']        = '';
        $this['publishing.dir.contents']    = '';
        $this['publishing.dir.resources']   = '';
        $this['publishing.dir.plugins']     = '';
        $this['publishing.dir.templates']   = '';
        $this['publishing.dir.output']      = '';
        $this['publishing.edition']         = '';
        $this['publishing.items']           = array();
        // the specific item currently being parsed/modified/decorated/...
        $this['publishing.active_item']     = array();
        $this['publishing.active_item.toc'] = array();
        $this['publishing.book.slug']       = '';
        $this['publishing.book.items']      = array();
        // holds all the generated slugs, to avoid repetitions
        $this['publishing.slugs']           = array();
        // holds all the internal links (used in html_chunked and epub editions)
        $this['publishing.links']           = array();
        $this['publishing.list.images']     = array();
        $this['publishing.list.tables']     = array();

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

        // -- configurator ----------------------------------------------------
        $this['configurator'] = $this->share(function ($app) {
            return new Configurator($app);
        });

        // -- validator -------------------------------------------------------
        $this['validator'] = $this->share(function ($app) {
            return new Validator($app);
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
        $this['parser.options'] = array(
            // available syntaxes: 'original', 'php-markdown-extra', 'easybook'
            'markdown_syntax'  => 'easybook',
        );

        $this['parser'] = $this->share(function ($app) {
            $format = strtolower($app['publishing.active_item']['config']['format']);

            if (in_array($format, array('md', 'mdown', 'markdown'))) {
                return new MarkdownParser($app);
            }

            // TODO: extensibility -> support several format parsers (RST, Textile, ...)
            throw new \Exception(sprintf(
                'Unknown "%s" format for "%s" content (easybook only supports Markdown)',
                $format,
                $app['publishing.active_item']['config']['content']
            ));
        });

        // -- twig ------------------------------------------------------------
        $this['twig.options'] = array(
            'autoescape'       => false,
            // 'cache'         => $app['app.dir.cache'].'/Twig,
            'charset'          => $this['app.charset'],
            'debug'            => $this['app.debug'],
            'strict_variables' => $this['app.debug'],
        );

        $this['twig.loader'] = $app->share(function() use ($app) {
            $theme  = ucfirst($app->edition('theme'));
            $format = Toolkit::camelize($app->edition('format'), true);
            // TODO: fix the following hack
            if ('Epub' == $format) {
                $format = 'Epub2';
            }

            $loader = new \Twig_Loader_Filesystem($app['app.dir.themes']);

            $themeTemplatePaths = array(
                // <easybook>/app/Resources/Themes/Base/<edition-type>/Templates/<template-name>.twig
                sprintf('%s/Base/%s/Templates', $app['app.dir.themes'], $format),
                // <easybook>/app/Resources/Themes/<theme>/Common/Templates/<template-name>.twig
                sprintf('%s/%s/Common/Templates', $app['app.dir.themes'], $theme),
                // <easybook>/app/Resources/Themes/<theme>/<edition-type>/Templates/<template-name>.twig
                sprintf('%s/%s/%s/Templates', $app['app.dir.themes'], $theme, $format),
            );

            foreach ($themeTemplatePaths as $path) {
                if (file_exists($path)) {
                    // the path is added twice because Twig doesn't support
                    // setting multiple namespaces for a single path
                    $loader->prependPath($path);
                    $loader->prependPath($path, 'theme');
                }
            }

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

        $this['twig'] = $app->share(function() use ($app) {
            $twig = new \Twig_Environment($app['twig.loader'], $app['twig.options']);
            $twig->addExtension(new TwigCssExtension());

            $twig->addGlobal('app', $app);

            if (null != $app->get('book')) {
                $twig->addGlobal('book', $app->get('book'));

                $publishingEdition = $app->get('publishing.edition');
                $editions = $app->book('editions');
                $twig->addGlobal('edition', $editions[$publishingEdition]);
            }

            return $twig;
        });

        // -- princeXML -------------------------------------------------------
        $this['prince.default_paths'] = array(
            '/usr/local/bin/prince',                         # Mac OS X
            '/usr/bin/prince',                               # Linux
            'C:\Program Files\Prince\engine\bin\prince.exe'  # Windows
        );

        $this['prince'] = $app->share(function () use ($app) {
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
                } else {
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

    public final function getVersion()
    {
        return static::VERSION;
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

    /**
     * Shortcut method to get the label of any element type.
     *
     * @param  string $element   The element type ('chapter', 'foreword', ...)
     * @param  array  $variables Optional variables used to render the label
     * @return string The label of the element or an empty string
     */
    public function getLabel($element, $variables = array())
    {
        // TODO: extensibility: each content should be able to override 'label' option
        $label = array_key_exists($element, $this['labels']['label'])
            ? $this['labels']['label'][$element]
            : '';

        // some elements (mostly chapters and appendices) have a different label for each level (h1, ..., h6)
        if (is_array($label)) {
            $index = $variables['item']['level']-1;
            $label = $label[$index];
        }

        return $this->renderString($label, $variables);
    }

    /**
     * Shortcut method to get the title of any element type.
     *
     * @param  string $element The element type ('chapter', 'foreword', ...)
     * @return string The title of the element or an empty string
     */
    public function getTitle($element)
    {
        return array_key_exists($element, $this['titles']['title'])
            ? $this['titles']['title'][$element]
            : '';
    }

    /**
     * Renders any string as a Twig template. It automatically injects two global
     * variables called 'book' and 'edition', which offer direct access to any
     * book or edition configuration option.
     * 
     * @param  string $string    The original content to render
     * @param  array  $variables Optional variables passed to the template
     */
    public function renderString($string, $variables = array())
    {
        $twig = new \Twig_Environment(new \Twig_Loader_String(), $this->get('twig.options'));

        if (null != $this->get('book')) {
            $twig->addGlobal('book', $this->get('book'));

            $publishingEdition = $this->get('publishing.edition');
            $editions = $this->book('editions');
            $twig->addGlobal('edition', $editions[$publishingEdition]);
        }

        return $twig->render($string, $variables);
    }

    /**
     * Renders any template (currently only supports Twig templates).
     * 
     * @param  string $template   The template name (it can include a namespace)
     * @param  array  $variables  Optional variables passed to the template
     * @param  string $targetFile Optional output file path. If set, the rendered
     *                            template is saved in this file.
     */
    public function render($template, $variables = array(), $targetFile = null)
    {
        if ('.twig' != substr($template, -5)) {
            throw new \RuntimeException(sprintf(
                'Unsupported format for "%s" template (easybook only supports Twig)',
                $template
            ));
        }

        $rendered = $this->get('twig')->render($template, $variables);

        if (null != $targetFile) {
            if (!is_dir($dir = dirname($targetFile))) {
                $this->get('filesystem')->mkdir($dir);
            }

            file_put_contents($targetFile, $rendered);
        }

        return $rendered;
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

        return $this->getFirstFileOrNull($templateName, $paths);
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

        return $this->getFirstFileOrNull($labelsFileName, $paths);
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

        return $this->getFirstFileOrNull($titlesFileName, $paths);
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

        return $this->getFirstFileOrNull($coverFileName, $paths);
    }

    /**
     * Looks for a file in several paths and it returns the absolute filepath
     * of the first file occurrence or null if no file is found in those paths.
     * 
     * @param  array $file  The file name
     * @param  array $paths The paths where the file can exist
     * @return string|null  The absolute filepath of the first found file or
     *                      null if the file isn't found in any of those paths.
     */
    public function getFirstFileOrNull($file, $paths)
    {
        foreach ($paths as $path) {
            if (file_exists($path.'/'.$file)) {
                return $path.'/'.$file;
            }
        }

        return null;
    }

    /**
     * Highlights the given code according to the specified programming language.
     *
     * @param  string $code     The source code to be highlighted
     * @param  string $language The name of the programming language used in the code
     * @return string           The highlighted code
     */
    public function highlight($code, $language = 'code')
    {
        // check if the code exists in the cache
        if ($this->edition('highlight_cache')) {
            // inspired by Twig_Environment -> getCacheFileName()
            // see https://github.com/fabpot/Twig/blob/master/lib/Twig/Environment.php
            $hash = md5($language.$code);
            $cacheDir = $this->get('app.dir.cache').'/GeSHi/'.substr($hash, 0, 2).'/'.substr($hash, 2, 2);
            $cacheFilename = $cacheDir.'/'.substr($hash, 4).'.txt';

            if (file_exists($cacheFilename)) {
                return file_get_contents($cacheFilename);
            }
        }

        // highlight the code useing GeSHi library
        $geshi = $this->get('geshi');
        if ('html' == $language) { $language = 'html5'; }

        $geshi->set_source($code);
        $geshi->set_language($language);
        $highlightedCode = $geshi->parse_code();

        // save the highlighted code in the cache
        if ($this->edition('highlight_cache')) {
            $this->get('filesystem')->mkdir($cacheDir);

            if (false === @file_put_contents($cacheFilename, $highlightedCode)) {
                throw new \RuntimeException(sprintf("ERROR: Failed to write cache file \n'%s'.", $cacheFilename));
            }
        }

        return $highlightedCode;
    }

    /**
     * Shortcut method to dispatch events.
     *
     * @param string $eventName   The name of the dispatched event
     * @param mixed  $eventObject The object that stores event data
     */
    public function dispatch($eventName, $eventObject = null)
    {
        $this->get('dispatcher')->dispatch($eventName, $eventObject);
    }

    /**
     * Shortcut to get/set book configuration options:
     *
     *   // returns 'author' option value
     *   $app->book('author');
     *
     *   // sets 'New author' as the value of 'author' option
     *   $app->book('author', 'New author');
     *
     * @param  mixed $key      The configuration option key
     * @param  mixed $newValue The new value of the configuration option
     * @return mixed It only returns a value when the second argument is null
     */
    public function book($key, $newValue = null)
    {
        if (null == $newValue) {
            $book = $this->get('book');

            return array_key_exists($key, $book) ? $book[$key] : null;
        } else {
            $book = $this->get('book');
            $book[$key] = $newValue;
            $this->set('book', $book);
        }
    }

    /**
     * Shortcut to get/set edition configuration options:
     *
     *   // returns 'page_size' option value
     *   $app->edition('page_size');
     *
     *   // sets 'US-letter' as the value of 'page_size' option
     *   $app->book('page_size', 'US-Letter');
     *
     * @param  mixed $key   The configuration option key
     * @param  mixed $value The new value of the configuration option
     * @return mixed It only returns a value when the second argument is null
     */
    public function edition($key, $newValue = null)
    {
        if (null == $newValue) {
            $publishingEdition = $this->get('publishing.edition');
            $editions = $this->book('editions');

            return array_key_exists($key, $editions[$publishingEdition] ?: array())
                   ? $editions[$publishingEdition][$key]
                   : null;
        } else {
            $book = $this->get('book');
            $publishingEdition = $this->get('publishing.edition');
            $book['editions'][$publishingEdition][$key] = $newValue;
            $this->set('book', $book);
        }
    }
}
