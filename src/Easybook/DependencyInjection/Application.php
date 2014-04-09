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
use Easybook\Configurator\BookConfigurator;
use Easybook\Providers\CodeHighlighterServiceProvider;
use Easybook\Providers\KindleGenServiceProvider;
use Easybook\Providers\ParserServiceProvider;
use Easybook\Providers\PrinceXMLServiceProvider;
use Easybook\Providers\PublisherServiceProvider;
use Easybook\Providers\SluggerServiceProvider;
use Easybook\Providers\TwigServiceProvider;
use Easybook\Util\Toolkit;
use Easybook\Util\Validator;

class Application extends \Pimple
{
    const VERSION = '5.0-DEV';

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
        $this['publishing.book.config']     = array('book' => array());
        $this['publishing.book.slug']       = '';
        $this['publishing.book.items']      = array();
        // the real TOC used to generate the book (needed for html_chunked editions)
        $this['publishing.book.toc']        = array();
        // holds all the internal links (used in html_chunked and epub editions)
        $this['publishing.links']           = array();
        $this['publishing.list.images']     = array();
        $this['publishing.list.tables']     = array();

        $this['publishing.edition.id'] = $this->share(function ($app) {
            if (null != $isbn = $app->edition('isbn')) {
                return array('scheme' => 'isbn', 'value' => $isbn);
            }

            // for ISBN-less books, generate a unique RFC 4211 UUID v4 ID
            return array('scheme' => 'URN', 'value' => Toolkit::uuid());
        });
        // maintained for backwards compatibility
        $this['publishing.id'] = $this->share(function () {
            trigger_error('The "publishing.id" option is deprecated since version 5.0 and will be removed in the future. Use "publishing.edition.id" instead.', E_USER_DEPRECATED);
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
        $this['filesystem'] = $this->share(function () {
            return new Filesystem();
        });

        // -- configurator ----------------------------------------------------
        $this['configurator'] = $this->share(function ($app) {
            return new BookConfigurator($app);
        });

        // -- validator -------------------------------------------------------
        $this['validator'] = $this->share(function ($app) {
            return new Validator($app);
        });

        $this->register(new PublisherServiceProvider());
        $this->register(new ParserServiceProvider());
        $this->register(new TwigServiceProvider());
        $this->register(new PrinceXMLServiceProvider());
        $this->register(new KindleGenServiceProvider());
        $this->register(new SluggerServiceProvider());
        $this->register(new CodeHighlighterServiceProvider());

        // -- labels ---------------------------------------------------------
        $this['labels'] = $app->share(function () use ($app) {
            $labels = Yaml::parse(
                $app['app.dir.translations'].'/labels.'.$app->book('language').'.yml'
            );

            // books can define their own labels files
            if (null != $customLabelsFile = $app->getCustomLabelsFile()) {
                $customLabels = Yaml::parse($customLabelsFile);

                return Toolkit::array_deep_merge_and_replace($labels, $customLabels);
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

                return Toolkit::array_deep_merge_and_replace($titles, $customTitles);
            }

            return $titles;
        });
    }

    public final function getVersion()
    {
        return static::VERSION;
    }

    /**
     * @deprecated Deprecated since version 5.0.
     *
     * Instead of:
     *   $value = $app->get('key');
     *
     * Use:
     *   $value = $app['key'];
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }

    /**
     * @deprecated Deprecated since version 5.0.
     *
     * Instead of:
     *   $app->set('key', $value);
     *
     * Use:
     *   $app['key'] = $value;
     */
    public function set($id, $value)
    {
        $this->offsetSet($id, $value);
    }

    /**
     * Appends the given value to the content of the container element identified
     * by the 'id' parameter. It only works for container elements that store arrays.
     *
     * @param  sintr $id    The id of the element that is modified
     * @param  mixed $value The value to append to the original element
     *
     * @return array        The resulting array element (with the new value appended)
     */
    public function append($id, $value)
    {
        $array = $this[$id];
        $array[] = $value;
        $this[$id] = $array;

        return $array;
    }

    /**
     * Registers a service provider.
     *
     * Code inspired by Silex\Application:register() method
     * (c) Fabien Potencier <fabien@symfony.com> (MIT license)
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     */
    public function register(ServiceProviderInterface $provider)
    {
        $provider->register($this);
    }

    /**
     * Transforms the string into a web-safe slug.
     *
     * @param  string  $string    The string to slug
     * @param  string  $separator Used between words and to replace illegal characters
     * @param  string  $prefix    Prefix to be appended at the beginning of the slug
     *
     * @return string             The generated slug
     */
    public function slugify($string, $separator = null, $prefix = null)
    {
        $slug = $this['slugger']->slugify($string, $separator);

        if (null != $prefix) {
            $slug = $prefix.$separator.$slug;
        }

        $this->append('slugger.generated_slugs', $slug);

        return $slug;
    }

    /**
     * Transforms the original string into a web-safe slug. It also ensures that
     * the generated slug is unique for the entire book (to do so, it stores
     * every slug generated since the beginning of the script execution).
     *
     * @param  string  $string    The string to slug
     * @param  string  $separator Used between words and to replace illegal characters
     * @param  string  $prefix    Prefix to be appended at the beginning of the slug
     *
     * @return string             The generated slug
     */
    public function slugifyUniquely($string, $separator = null, $prefix = null)
    {
        $defaultOptions = $this['slugger.options'];

        $separator = $separator ?: $defaultOptions['separator'];
        $prefix    = $prefix    ?: $defaultOptions['prefix'];

        $slug = $this->slugify($string, $separator, $prefix);

        if (null != $prefix) {
            $slug = $prefix.$separator.$slug;
        }

        // ensure the uniqueness of the slug
        $occurrences = array_count_values($this['slugger.generated_slugs']);
        $count = $occurrences[$slug];
        if ($count > 1) {
            $slug .= $separator.$count;
        }

        return $slug;
    }

    /**
     * Shortcut method to get the label of any element type.
     *
     * @param  string $element   The element type ('chapter', 'foreword', ...)
     * @param  array  $variables Optional variables used to render the label
     *
     * @return string The label of the element or an empty string
     */
    public function getLabel($element, $variables = array())
    {
        $label = isset($this['labels']['label'][$element])
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
     *
     * @return string The title of the element or an empty string
     */
    public function getTitle($element)
    {
        return isset($this['titles']['title'][$element])
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
     *
     * @return string The result of rendering the original string as a Twig template
     */
    public function renderString($string, $variables = array())
    {
        $twig = new \Twig_Environment(new \Twig_Loader_String(), $this['twig.options']);

        $twig->addGlobal('app', $this);

        if (null != $bookConfig = $this['publishing.book.config']) {
            $twig->addGlobal('book', $bookConfig['book']);

            $publishingEdition = $this['publishing.edition'];
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
     *
     * @return string The result of rendering the Twig template
     *
     *  @throws \RuntimeException  If the given template is not a Twig template
     */
    public function render($template, $variables = array(), $targetFile = null)
    {
        if ('.twig' != substr($template, -5)) {
            throw new \RuntimeException(sprintf(
                'Unsupported format for "%s" template (easybook only supports Twig)',
                $template
            ));
        }

        $rendered = $this['twig']->render($template, $variables);

        if (null != $targetFile) {
            if (!is_dir($dir = dirname($targetFile))) {
                $this['filesystem']->mkdir($dir);
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
     * @param string $templateName The name of the template to look for
     *
     * @return string|null The path of the custom template or null if there is none
     */
    public function getCustomTemplate($templateName)
    {
        $paths = array(
            $this['publishing.dir.templates'].'/'.$this['publishing.edition'],
            $this['publishing.dir.templates'].'/'.$this->edition('format'),
            $this['publishing.dir.templates']
        );

        return $this->getFirstExistingFile($templateName, $paths);
    }

    /*
     * It looks for custom book labels. The search order is:
     *   1. <book>/Resources/Translations/<edition-name>/labels.<book-language>.yml
     *   2. <book>/Resources/Translations/<edition-format>/labels.<book-language>.yml
     *   3. <book>/Resources/Translations/labels.<book-language>.yml
     *
     * @return string|null The path of the custom labels file or null if there is none
     */
    public function getCustomLabelsFile()
    {
        $labelsFileName = 'labels.'.$this->book('language').'.yml';
        $paths = array(
            $this['publishing.dir.resources'].'/Translations/'.$this['publishing.edition'],
            $this['publishing.dir.resources'].'/Translations/'.$this->edition('format'),
            $this['publishing.dir.resources'].'/Translations'
        );

        return $this->getFirstExistingFile($labelsFileName, $paths);
    }

    /*
     * It looks for custom book titles. The search order is:
     *   1. <book>/Resources/Translations/<edition-name>/titles.<book-language>.yml
     *   2. <book>/Resources/Translations/<edition-format>/titles.<book-language>.yml
     *   3. <book>/Resources/Translations/titles.<book-language>.yml
     *
     * @return string|null The path of the custom titles file or null if there is none
     */
    public function getCustomTitlesFile()
    {
        $titlesFileName = 'titles.'.$this->book('language').'.yml';
        $paths = array(
            $this['publishing.dir.resources'].'/Translations/'.$this['publishing.edition'],
            $this['publishing.dir.resources'].'/Translations/'.$this->edition('format'),
            $this['publishing.dir.resources'].'/Translations'
        );

        return $this->getFirstExistingFile($titlesFileName, $paths);
    }

    /*
     * It looks for custom book cover images. The search order is:
     *   1. <book>/Resources/Templates/<edition-name>/cover.jpg
     *   2. <book>/Resources/Templates/<edition-format>/cover.jpg
     *   3. <book>/Resources/Templates/cover.jpg
     *
     * @return string|null The path of the custom cover image or null if there is none
     */
    public function getCustomCoverImage()
    {
        $coverFileName = 'cover.jpg';
        $paths = array(
            $this['publishing.dir.templates'].'/'.$this['publishing.edition'],
            $this['publishing.dir.templates'].'/'.$this->edition('format'),
            $this['publishing.dir.templates']
        );

        return $this->getFirstExistingFile($coverFileName, $paths);
    }

    /**
     * Looks for a file in several paths and it returns the absolute filepath
     * of the first file occurrence or null if no file is found in those paths.
     * 
     * @param  array $file  The name of the file to look for
     * @param  array $paths The paths where the file can exist
     *
     * @return string|null  The absolute filepath of the first found file or
     *                      null if the file isn't found in any of those paths.
     */
    public function getFirstExistingFile($file, $paths)
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
     *
     * @return string           The highlighted code
     *
     * @throws \RuntimeException If the cache used to store the highlighted code isn't writable
     */
    public function highlight($code, $language = 'code')
    {
        return $this['highlighter']->highlight($code, $language);
    }

    /**
     * Shortcut method to dispatch events.
     *
     * @param string $eventName   The name of the dispatched event
     * @param mixed  $eventObject The object that stores event data
     */
    public function dispatch($eventName, $eventObject = null)
    {
        $this['dispatcher']->dispatch($eventName, $eventObject);
    }

    /**
     * It loads the full book configuration by combining all the different sources
     * (config.yml file, console command option and default values). It also loads
     * the edition configuration and resolves the edition inheritance (if used).
     *
     * @param string $configurationViaCommand The configuration options provided via the console command
     */
    public function loadBookConfiguration($configurationViaCommand = "")
    {
        $config = $this['configurator']->loadBookConfiguration($this['publishing.dir.book'], $configurationViaCommand);
        $this['publishing.book.config'] = $config;

        $this['validator']->validatePublishingEdition($this['publishing.edition']);

        $config = $this['configurator']->loadEditionConfiguration();
        $this['publishing.book.config'] = $config;

        $config = $this['configurator']->processConfigurationValues();
        $this['publishing.book.config'] = $config;
    }

    /**
     * It loads the (optional) easybook configuration parameters defined by the book.
     */
    public function loadEasybookConfiguration()
    {
        $bookFileConfig = $this['configurator']->loadBookFileConfiguration($this['publishing.dir.book']);

        if (!isset($bookFileConfig['easybook'])) {
            return;
        }

        foreach ($bookFileConfig['easybook']['parameters'] as $option => $value) {
            if (is_array($value)) {
                $previousArray = $this->offsetExists($option) ? $this[$option] : array();
                $newArray = array_merge($previousArray, $value);
                $this[$option] = $newArray;
            } else {
                $this[$option] = $value;
            }
        }
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
     *
     * @return mixed It only returns a value when the second argument is null
     */
    public function book($key, $newValue = null)
    {
        $bookConfig = $this['publishing.book.config'];

        if (null == $newValue) {
            return isset($bookConfig['book'][$key]) ? $bookConfig['book'][$key] : null;
        } else {
            $bookConfig['book'][$key] = $newValue;
            $this['publishing.book.config'] = $bookConfig;
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
     * @param  mixed $key      The configuration option key
     * @param  mixed $newValue The new value of the configuration option
     *
     * @return mixed           It only returns a value when the second argument is null
     */
    public function edition($key, $newValue = null)
    {
        $bookConfig = $this['publishing.book.config'];
        $publishingEdition = $this['publishing.edition'];

        if (null == $newValue) {
            return isset($bookConfig['book']['editions'][$publishingEdition][$key])
                ? $bookConfig['book']['editions'][$publishingEdition][$key]
                : null;
        } else {
            $bookConfig['book']['editions'][$publishingEdition][$key] = $newValue;
            $this['publishing.book.config'] = $bookConfig;
        }
    }
}
