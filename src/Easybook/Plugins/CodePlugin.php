<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Plugins;

use Easybook\DependencyInjection\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;

/**
 * It parses and (optionally) highlights the syntax of the code listings.
 */
class CodePlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_PARSE  => array('parseCodeBlocks', -500),
            Events::POST_PARSE => array('fixParsedCodeBlocks', -500),
        );
    }

    /**
     * It parses different code blocks types (Markdown classic, fenced
     * and GitHub).
     *
     * @see 'Code block types' section in easybook-doc-en/05-publishing-html-books.md
     *
     * @param ParseEvent $event
     */
    public function parseCodeBlocks(ParseEvent $event)
    {
        $codeBlockType = $event->app['parser.options']['code_block_type'];

        switch ($codeBlockType) {
            case 'fenced':
                $this->parseFencedTypeCodeBlocks($event);
                break;

            case 'github':
                $this->parseGithubTypeCodeBlocks($event);
                break;

            case 'markdown':
            default:
                $this->parseMarkdownTypeCodeBlocks($event);
                break;
        }
    }

    /**
     * It fixes the resulting contents of the parsed code blocks.
     *
     * @param ParseEvent $event The object that contains the item being processed
     */
    public function fixParsedCodeBlocks(ParseEvent $event)
    {
        $item = $event->getItem();

        // unescape yaml-style comments that before parsing could
        // be interpreted as Markdown first-level headings
        $item['content'] = str_replace('&#35;', '#', $item['content']);

        $event->setItem($item);
    }

    /**
     * It parses the code blocks of the item content that use the
     * Markdown style for code blocks:
     *
     *   * every line of code indented by 4 spaces or a tab
     *   * (optionally) the firs line describes the language of the code
     *
     * Examples:
     *
     *     [php]
     *     $lorem = 'ipsum';
     *     // ...
     *
     *     [javascript]
     *     var lorem = 'ipsum';
     *     // ...
     *
     *     [code]
     *     Generic code not associated with any language
     *
     * @param ParseEvent $event The event object that provides access to the $app and
     *                          the $item being parsed
     */
    private function parseMarkdownTypeCodeBlocks(ParseEvent $event)
    {
        // variable needed for PHP 5.3
        $self = $this;

        $item = $event->getItem();
        // regexp copied from PHP-Markdown
        $item['original'] = preg_replace_callback('{
                (?:\n(?<indent>(?:[ ]{4})*)\n|\A\n?)
                (?<code>                    # $1 = the code block -- one or more lines, starting with a space/tab
                    (?:(?>
                        ^\g{indent}[ ]{4}   # Lines must start with a tab or a tab-width of spaces
                        .*\n
                    ))*
                    (?:(?>
                        ^\g{indent}[ ]{4}   # Lines must start with a tab or a tab-width of spaces
                        .*
                    ))
                )
            }xm',
            function ($matches) use ($self, $event) {
                $code = $matches['code'];
                $indent = $matches['indent'];

                // outdent codeblock
                $code = preg_replace('/^(' . $indent . '[ ]{4})/m', '', $code);

                // if present, strip code language declaration ([php], [js], ...)
                $language = 'code';
                $code = preg_replace_callback('{
                        ^\[(?<lang>.*)\]\n(?<code>.*)
                    }x',
                    function ($matches) use (&$language) {
                        $language = trim($matches['lang']);

                        return $matches['code'];
                    },
                    $code
                );

                $code = $self->highlightAndDecorateCode($code, $language, $event->app);

                // indent code block
                return "\n$indent\n$indent" . str_replace("\n", "\n" . $indent, $code);
            },
            $item['original']
        );
        $event->setItem($item);
    }

    /**
     * It parses the code blocks of the item content that use the
     * GitHub style for code blocks:
     *   * the code listing starts with ```
     *   * (optionally) followed by the programming language name
     *   * the lines of code don't include any leading tab or whitespace
     *   * the code listing ends with ```
     *
     * Examples:
     *
     *     ```php
     *     $lorem = 'ipsum';
     *     // ...
     *     ```
     *
     *     ```javascript
     *     var lorem = 'ipsum';
     *     // ...
     *     ```
     *
     *     ```
     *     Generic code not associated with any language
     *     ```
     *
     * @param ParseEvent $event The event object that provides access to the $app and
     *                          the $item being parsed
     */
    private function parseGithubTypeCodeBlocks(ParseEvent $event)
    {
        // variable needed for PHP 5.3
        $self = $this;

        $item = $event->getItem();
        // regexp adapted from PHP-Markdown
        $item['original'] = preg_replace_callback('{
                (?:\n|\A)
                # 1: Opening marker
                (
                    `{3} # Marker: three ` characters
                )
                [ ]*
                (?:
                    \.?([-_:a-zA-Z0-9]+) # 2: optional language name
                )?
                [ ]* \n # Whitespace and newline following marker.

                # 4: Content
                (
                    (?>
                        (?!\1 [ ]* \n)    # Not a closing marker.
                        .*\n+
                    )+
                )

                # Closing marker.
                \1 [ ]* \n
            }Uxm',
            function ($matches) use ($self, $event) {
                $language = $matches[2];

                // codeblocks always end with an empty new line (due to the regexp used)
                // the current solution rtrims() the whole block. This would not work
                // in the (very) rare situations where a code block must end with
                // whitespaces, tabs or new lines
                $code = rtrim($matches[3]);

                if ('' == $language) {
                    $language = 'code';
                }

                $code = $self->highlightAndDecorateCode($code, $language, $event->app);

                return "\n\n" . $code;
            },
            $item['original']
        );
        $event->setItem($item);
    }

    /**
     * It parses the code blocks of the item content that use the
     * fenced style for code blocks:
     *   * the code listing starts with at least three ~~~
     *   * (optionally) followed by a whitespace + a dot + the programming language name
     *   * the lines of code don't include any leading tab or whitespace
     *   * the code listing ends with the same number of opening ~~~
     *
     * Examples:
     *
     *     ~~~ .php
     *     $lorem = 'ipsum';
     *     // ...
     *     ~~~
     *
     *     ~~~~~~~~~~ .javascript
     *     var lorem = 'ipsum';
     *     // ...
     *     ~~~~~~~~~~
     *
     *     ~~~
     *     Generic code not associated with any language
     *     ~~~
     *
     * @param ParseEvent $event The event object that provides access to the $app and
     *                          the $item being parsed
     */
    private function parseFencedTypeCodeBlocks(ParseEvent $event)
    {
        // variable needed for PHP 5.3
        $self = $this;

        $item = $event->getItem();
        // regexp adapted from PHP-Markdown
        $item['original'] = preg_replace_callback('{
                (?:\n|\A)
                # 1: Opening marker
                (
                    ~{3,} # Marker: three tilde or more.
                )
                [ ]*
                (?:
                    \.?([-_:a-zA-Z0-9]+) # 2: standalone class name
                )?
                [ ]* \n # Whitespace and newline following marker.

                # 4: Content
                (
                    (?>
                        (?!\1 [ ]* \n)    # Not a closing marker.
                        .*\n+
                    )+
                )

                # Closing marker.
                \1 [ ]* \n
            }Uxm',
            function ($matches) use ($self, $event) {
                $language = $matches[2];

                // codeblocks always end with an empty new line (due to the regexp used)
                // the current solution rtrims() the whole block. This would not work
                // in the (very) rare situations where a code block must end with
                // whitespaces, tabs or new lines
                $code = rtrim($matches[3]);

                if ('' == $language) {
                    $language = 'code';
                }

                $code = $self->highlightAndDecorateCode($code, $language, $event->app);

                return "\n\n" . $code;
            },
            $item['original']
        );
        $event->setItem($item);
    }

    /**
     * It highlights the given code using the given programming language syntax
     * and decorates the result with the Twig template associated with the
     * code fragments.
     *
     * @param string $code     The source code to highlight and decorate
     * @param string $language The programming language associated with the code
     * @param Application $app The application object needed to highlight and decorate
     *
     * @return string The resulting code after the highlight and rendering process
     */
    public function highlightAndDecorateCode($code, $language, Application $app)
    {
        if ($app->edition('highlight_code')) {
            // highlight code if the edition wants to
            $code = $app->highlight($code, $language);
        }
        else {
            // escape code to show it instead of interpreting it

            // yaml-style comments could be interpreted as Markdown headings
            // replace any starting # character by its HTML entity (&#35;)
            $code = '<pre>'
                . preg_replace('/^# (.*)/', "&#35; $1", htmlspecialchars($code))
                . '</pre>';
        }

        $code = $app->render('code.twig', array(
            'item' => array(
                'content'  => $code,
                'language' => $language,
                'number'   => '',
                'slug'     => ''
            )
        ));

        return $code;
    }
}
