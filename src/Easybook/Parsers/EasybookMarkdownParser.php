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

use Easybook\DependencyInjection\Application;
use Michelf\MarkdownExtra as ExtraMarkdownParser;

/**
 * This class implements the exclusive 'easybook' syntax that augments the
 * original Markdown basic syntax.
 * 
 * In addition, it overrides some PHP Markdown Extra methods to improve
 * performance.
 */
class EasybookMarkdownParser extends ExtraMarkdownParser implements ParserInterface
{
    private $app;
    private $admonitionTypes;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->app['publishing.active_item.toc'] = array();

        $this->admonitionTypes = array(
            'A' => 'aside',
            'N' => 'note',
            'W' => 'warning',
            'T' => 'tip',
            'E' => 'error',
            'I' => 'information',
            'Q' => 'question',
            'D' => 'discussion'
        );

        $this->span_gamut += array(
            'doPageBreaks' => 20
        );

        $this->block_gamut += array(
            'doAdmonitions' => 55
        );

        parent::__construct();
    }

    /**
     * It removes any \t character present in the given text content.
     * This method overrides the original implementation to improve
     * its performance. Code copied from:
     * http://github.com/KnpLabs/KnpMarkdownBundle/blob/master/Parser/MarkdownParser.php
     *
     * @param $text The original text with the tabular characters
     *
     * @return string The result of deleting the tabs from the original text
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
     *
     * @param string text The original Markdown content
     *
     * @return string The original content with the Markdown headings replaced
     *                by the HTML headings with 'id' attributes
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

    /**
     * Callback method to transform the original Markdown headings
     * into regular HTML headings (setext-style headers version).
     *
     * @param array $matches The array of headings to parse
     *
     * @return string The HTML contents of the parsed heading
     */
    public function _doHeaders_callback_setext($matches) {
        if ($matches[3] == '-' && preg_match('{^- }', $matches[1])) {
            return $matches[0];
        }
        $level = $matches[3]{0} == '=' ? 1 : 2;
        
        # added by easybook
        $title = $matches[1];
        $id    = isset($matches[2]) ? $matches[2] : '';
        if ('' == $id || null == $id) {
            $id = $this->app->slugifyUniquely(strip_tags($title));
        }

        $block = "<h$level id=\"$id\">".$this->runSpanGamut($title)."</h$level>";
        
        $this->app->append('publishing.active_item.toc', array(
            'level' => $level,
            'title' => $this->runSpanGamut($title),
            'slug'  => $id
        ));
        
        return "\n" . $this->hashBlock($block) . "\n\n";
    }

    /**
     * Callback method to transform the original Markdown headings
     * into regular HTML headings (atx-style headers version).
     *
     * @param array $matches The array of headings to parse
     *
     * @return string The HTML contents of the parsed heading
     */
    public function _doHeaders_callback_atx($matches) {
        $level = strlen($matches[1]);
        $title = $this->runSpanGamut($matches[2]);
        $id    = isset($matches[3]) ? $matches[3] : '';
        if ('' == $id || null == $id) {
            $id = $this->app->slugifyUniquely($this->unhash($title));
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
     *
     * @param array $matches The array of images to parse
     *
     * @return string The HTML string that represents the original Markdown image element
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

    /**
     * It performs the same operations on images as the _doImages_reference_callback()
     * method. The difference is that this callback is only applied to inline images.
     *
     * @param  array  $matches The array of images to parse
     *
     * @return string          The HTML string that represents the original Markdown image element
     */
    public function _doImages_inline_callback($matches) {
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

    /**
     * easybook supports the following formats to force page breaks:
     *
     *   {pagebreak}   (the format used by leanpub)
     *   <!--BREAK-->  (the format used by marked)
     */
    public function doPageBreaks($text)
    {
        return str_replace(
            '{pagebreak}',
            $this->hashBlock('<br class="page-break" />')."\n",
            $text
        );
    }

    /**
     * easybook supports several kinds of admonitions. Their syntax is very
     * similar to blockquotes and it's based on LeanPub and Marked:
     *
     * Asides / Sidebars:
     *   A> ...
     *   A> ...
     *
     * Notes:
     *   N> ...
     *   N> ...
     *
     * Similar syntax for warnings (W>), tips (T>), errors (E>), information (I>)
     * questions (Q>) and discussions (D>).
     *
     * @param string text The original Markdown content
     *
     * @return string The original content with the Markdown admonitions replaced
     *                by the corresponding HTML admonitions
     */
    public function doAdmonitions($text)
    {
        $text = preg_replace_callback('/
            (
                (?>^[ ]*(['.implode('', array_keys($this->admonitionTypes)).'])>[ ]?.+\n)+
            )
            /xm',
            array(&$this, '_doAdmonitions_callback'),
            $text
        );

        return $text;
    }

    public function _doAdmonitions_callback($matches) {
        $content = $matches[1];

        # trim one level of quoting - trim whitespace-only lines
        $content = preg_replace(
            '/^[ ]*(['.implode('', array_keys($this->admonitionTypes)).'])>[ ]?|^[ ]+$/m',
            '',
            $content
        );

        $content = $this->runBlockGamut($content);

        $content = preg_replace('/^/m', "  ", $content);

        # These leading spaces cause problem with <pre> content,
        # so we need to fix that:
        $content = preg_replace_callback(
            '{(\s*<pre>.+?</pre>)}sx',
            function($submatches) {
                $pre = $submatches[1];
                $pre = preg_replace('/^  /m', '', $pre);

                return $pre;
            },
            $content
        );

        $type = $this->admonitionTypes[trim($matches[2])];

        return "\n".$this->hashBlock("<div class=\"admonition $type\">\n$content\n</div>")."\n\n";
    }

    /**
     * easybook supports three different code block types thanks to the
     * Code Plugin. Therefore, it doesn't use the fenced code block parsing
     * of the PHP Markdown library. This method overrides the original method
     * to do nothing with these code blocks.
     */
    public function doFencedCodeBlocks($text)
    {
        return $text;
    }
}