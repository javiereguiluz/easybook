<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Parsers;

use Easybook\Parsers\ParserInterface;
use Easybook\DependencyInjection\Application;
use dflydev\markdown\MarkdownParser as OriginalMarkdownParser;
use dflydev\markdown\MarkdownExtraParser as ExtraMarkdownParser;

/**
 * This class implements a full-featured Markdown parser.
 */
class MarkdownParser implements ParserInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Transforms the original Markdown content into the desired output format.
     * 
     * @param  string $content      The original content to be parsed
     * @param  string $outputFormat The desired output format (it only supports 'html' for now)
     * @return string               The parsed content
     */
    public function transform($content, $outputFormat = 'html')
    {
        $supportedFormats = array('epub', 'epub2', 'epub3', 'html', 'html_chunked', 'pdf');

        if (!in_array($outputFormat, $supportedFormats)) {
            throw new \Exception(sprintf('No markdown parser available for "%s" format',
                $outputFormat
            ));
        }

        $syntax = $this->app['parser.options']['markdown_syntax'];
        
        return $this->transformToHtml($content, $syntax);
    }

    /**
     * Transforms Markdown input to HTML output.
     * 
     * @param  string $content The original content to be parsed
     * @param  string $syntax  The Markdown syntax to use (original, PHP Extra, easybook, ...)
     * @return string          The parsed HTML output
     */
    private function transformToHtml($content, $syntax)
    {
        $supportedSyntaxes = array('original', 'php-markdown-extra', 'easybook');
        
        if (!in_array($syntax, $supportedSyntaxes)) {
            throw new \Exception(sprintf('Unknown "%s" Markdown syntax (options available: %s)',
                $syntax, implode(', ', $supportedSyntaxes)
            ));
        }

        switch ($syntax) {
            case 'original':
                $parser = new OriginalMarkdownParser();
                break;

            case 'php-markdown-extra':
                $parser = new ExtraMarkdownParser();
                break;

            case 'easybook':
                $parser = new EasybookMarkdownParser($this->app);
                break;
        }

        return $parser->transform($content);
    }
}


/**
 * This class implements the exclusive 'easybook' syntax that augments the
 * original Markdown basic syntax.
 * 
 * In addition, it overrides some PHP Markdown Extra methods to improve
 * performance.
 */
class EasybookMarkdownParser extends ExtraMarkdownParser
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->app->set('publishing.active_item.toc', array());

        parent::__construct();
    }

    /**
     * Improves the performance of the original method. Copied from:
     * http://github.com/KnpLabs/KnpMarkdownBundle/blob/master/Parser/MarkdownParser.php
     */
    public function detab($text)
    {
        return str_replace("\t", str_repeat(' ', $this->tab_width), $text);
    }

    /**
     * Improves the performance of the original method. Copied from:
     * http://github.com/KnpLabs/KnpMarkdownBundle/blob/master/Parser/MarkdownParser.php
     */
    public function _initDetab()
    {
        return;
    }

    /**
     * easybook automatically adds 'id' attributes to generated headings.
     * 
     * If the heading defines its 'id' via ' {#my-custom-id}' syntax, easybook
     * maintains it. If the heading doesn't define an 'id', easybook generates
     * a unique 'id'  based on the slugged title:
     *
     * # My first header # {#chapter-title} -> <h1 id="chapter-title">My first header</h1>
     * # My first header #                  -> <h1 id="my-first-header">My first header</h1>
     */
    public function doHeaders($text) {
    #
    # Redefined to add id attribute support.
    #
        # Setext-style headers:
        #     Header 1  {#header1}
        #     ========
        #  
        #     Header 2  {#header2}
        #     --------
        #
        $text = preg_replace_callback(
            '{
                (^.+?)                              # $1: Header text
                (?:[ ]+\{\#([-_:a-zA-Z0-9]+)\})?    # $2: Id attribute
                [ ]*\n(=+|-+)[ ]*\n+                # $3: Header footer
            }mx',
            array(&$this, '_doHeaders_callback_setext'), $text);

        # atx-style headers:
        #   # Header 1        {#header1}
        #   ## Header 2       {#header2}
        #   ## Header 2 with closing hashes ##  {#header3}
        #   ...
        #   ###### Header 6   {#header2}
        #
        $text = preg_replace_callback('{
                ^(\#{1,6})  # $1 = string of #\'s
                [ ]*
                (.+?)       # $2 = Header text
                [ ]*
                \#{0,6}     # added by easybook -> optional closing #\'s (not counted)
                (?:[ ]+\{\#([-_:a-zA-Z0-9]+)\})? # id attribute
                [ ]*
                \n+
            }xm',
            array(&$this, '_doHeaders_callback_atx'), $text);

        return $text;
    }

    public function _doHeaders_callback_setext($matches) {
        if ($matches[3] == '-' && preg_match('{^- }', $matches[1])) {
            return $matches[0];
        }
        $level = $matches[3]{0} == '=' ? 1 : 2;
        
        # added by easybook
        $title = $matches[1];
        $id    = array_key_exists(2, $matches) ? $matches[2] : '';
        if ('' == $id || null == $id) {
            $id = $this->app->get('slugger')->slugify(strip_tags($title));
        }

        $block = "<h$level id=\"$id\">".$this->runSpanGamut($title)."</h$level>";
        
        $this->app->append('publishing.active_item.toc', array(
            'level' => $level,
            'title' => $this->runSpanGamut($title),
            'slug'  => $id
        ));
        
        return "\n" . $this->hashBlock($block) . "\n\n";
    }

    public function _doHeaders_callback_atx($matches) {
        $level = strlen($matches[1]);
        $title = $this->runSpanGamut($matches[2]);
        $id    = array_key_exists(3, $matches) ? $matches[3] : '';
        if ('' == $id || null == $id) {
            $id = $this->app->get('slugger')->slugify($this->unhash($title));
        }

        $block = "<h$level id=\"$id\">".$title."</h$level>";

        $this->app->append('publishing.active_item.toc', array(
            'level' => $level,
            'title' => $this->unhash($title),
            'slug'  => $id
        ));
        
        return "\n" . $this->hashBlock($block) . "\n\n";
    }

    /**
     * easybook allows to set image alignment using a syntax trick:
     * 
     * // regular image not aligned
     * ![Test image](figure1.png)
     *
     * // "alt text" has a whitespace on the left -> the image is left aligned
     * ![ Test image](figure1.png)
     *
     * // "alt text" has a whitespace on the right -> the image is right aligned
     * ![Test image ](figure1.png)
     *
     * // "alt text" has whitespaces both on the left and on the right -> the image is centered
     * ![ Test image ](figure1.png)
     */
    public function _doImages_reference_callback($matches) {
        $whole_match = $matches[1];
        $alt_text    = $matches[2];
        $link_id     = strtolower($matches[3]);

        $align = '';
        if (' ' == substr($alt_text, 0, 1)) {
            if (' ' == substr($alt_text, -1)) {
                $align = 'center';
            }
            else {
                $align = 'left';
            }
        }
        elseif (' ' == substr($alt_text, -1)) {
            $align = 'right';
        }

        if ($link_id == "") {
            $link_id = strtolower($alt_text); # for shortcut links like ![this][].
        }

        $alt_text = $this->encodeAttribute(trim($alt_text));
        if (isset($this->urls[$link_id])) {
            $url = $this->encodeAttribute($this->urls[$link_id]);
            $result = "<img src=\"$url\" alt=\"$alt_text\"";
            if (isset($this->titles[$link_id])) {
                $title = $this->titles[$link_id];
                $title = $this->encodeAttribute($title);
                $result .=  " title=\"$title\"";
            }
            $result .= $this->empty_element_suffix;

            if ('' != $align) {
                $result = sprintf('<div class="%s">%s</div>', $align, $result);
            }

            $result = $this->hashPart($result);
        }
        else {
            # If there's no such link ID, leave intact:
            $result = $whole_match;
        }

        return $result;
    }

    public function _doImages_inline_callback($matches) {
        $whole_match = $matches[1];
        $alt_text    = $matches[2];
        $url         = $matches[3] == '' ? $matches[4] : $matches[3];
        $title       =& $matches[7];

        $align = '';
        if (' ' == substr($alt_text, 0, 1)) {
            if (' ' == substr($alt_text, -1)) {
                $align = 'center';
            }
            else {
                $align = 'left';
            }
        }
        elseif (' ' == substr($alt_text, -1)) {
            $align = 'right';
        }

        $alt_text = $this->encodeAttribute(trim($alt_text));
        $url = $this->encodeAttribute($url);

        $result = "<img src=\"$url\" alt=\"$alt_text\"";
        if (isset($title)) {
            $title = $this->encodeAttribute($title);
            $result .=  " title=\"$title\""; # $title already quoted
        }
        $result .= $this->empty_element_suffix;

        if ('' != $align) {
            $result = sprintf('<div class="%s">%s</div>', $align, $result);
        }

        return $this->hashPart($result);
    }
}