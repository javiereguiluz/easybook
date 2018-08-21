<?php declare(strict_types=1);

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Plugins;

use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It parses and (optionally) highlights the syntax of the code listings.
 */
final class CodePlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_PARSE => ['parseCodeBlocks', -500],
            Events::POST_PARSE => ['fixParsedCodeBlocks', -500],
        ];
    }

    /**
     * It parses different code blocks types (Markdown classic, fenced
     * and GitHub).
     *
     * @see 'Code block types' section in easybook-doc-en/05-publishing-html-books.md
     */
    public function parseCodeBlocks(ParseEvent $parseEvent): void
    {
        $codeBlockType = $parseEvent->app['parser.options']['code_block_type'];

        switch ($codeBlockType) {
            case 'fenced':
                $this->parseFencedTypeCodeBlocks($parseEvent);
                break;

            case 'github':
                $this->parseGithubTypeCodeBlocks($parseEvent);
                break;

            case 'markdown':
            default:
                $this->parseMarkdownTypeCodeBlocks($parseEvent);
                break;
        }
    }

    /**
     * It fixes the resulting contents of the parsed code blocks.
     *
     * @param ParseEvent $event The object that contains the item being processed
     */
    public function fixParsedCodeBlocks(ParseEvent $parseEvent): void
    {
        $item = $parseEvent->getItem();

        // unescape yaml-style comments that before parsing could
        // be interpreted as Markdown first-level headings
        $item['content'] = str_replace('&#35;', '#', $item['content']);

        $parseEvent->setItem($item);
    }

    /**
     * It highlights the given code using the given programming language syntax
     * and decorates the result with the Twig template associated with the
     * code fragments.
     *
     * @param string      $code     The source code to highlight and decorate
     * @param string      $language The programming language associated with the code
     * @param Application $app      The application object needed to highlight and decorate
     *
     * @return string The resulting code after the highlight and rendering process
     */
    public function highlightAndDecorateCode(string $code, string $language, Application $application): string
    {
        if ($application->edition('highlight_code')) {
            // highlight code if the edition wants to
            $code = $application->highlight($code, $language);
        } else {
            // escape code to show it instead of interpreting it

            // yaml-style comments could be interpreted as Markdown headings
            // replace any starting # character by its HTML entity (&#35;)
            $code = '<pre>'
                . preg_replace('/^# (.*)/', '&#35; $1', htmlspecialchars($code))
                . '</pre>';
        }

        return $application->render('code.twig', [
            'item' => [
                'content' => $code,
                'language' => $language,
                'number' => '',
                'slug' => '',
            ],
        ]);
    }

    /**
     * It parses the code blocks of the item content that use the
     * Markdown style for code blocks:.
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
    private function parseMarkdownTypeCodeBlocks(ParseEvent $parseEvent): void
    {
        // variable needed for PHP 5.3
        $self = $this;

        $item = $parseEvent->getItem();
        // regexp copied from PHP-Markdown
        $item['original'] = preg_replace_callback(
            '{
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
            function ($matches) use ($self, $parseEvent) {
                $code = $matches['code'];
                $indent = $matches['indent'];

                // outdent codeblock
                $code = preg_replace('/^(' . $indent . '[ ]{4})/m', '', $code);

                // if present, strip code language declaration ([php], [js], ...)
                $language = 'code';
                $code = preg_replace_callback(
                    '{
                        ^\[(?<lang>.*)\]\n(?<code>.*)
                    }x',
                    function ($matches) use (&$language) {
                        $language = trim($matches['lang']);

                        return $matches['code'];
                    },
                    $code
                );

                $code = $self->highlightAndDecorateCode($code, $language, $parseEvent->app);

                // indent code block
                return "\n${indent}\n${indent}" . str_replace("\n", "\n" . $indent, $code);
            },
            $item['original']
        );
        $parseEvent->setItem($item);
    }

    /**
     * It parses the code blocks of the item content that use the
     * GitHub style for code blocks:
     *   * the code listing starts with ```
     *   * (optionally) followed by the programming language name
     *   * the lines of code don't include any leading tab or whitespace
     *   * the code listing ends with ```.
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
    private function parseGithubTypeCodeBlocks(ParseEvent $parseEvent): void
    {
        // variable needed for PHP 5.3
        $self = $this;

        $item = $parseEvent->getItem();
        // regexp adapted from PHP-Markdown
        $item['original'] = preg_replace_callback(
            '{
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
            function ($matches) use ($self, $parseEvent) {
                $language = $matches[2];

                // codeblocks always end with an empty new line (due to the regexp used)
                // the current solution rtrims() the whole block. This would not work
                // in the (very) rare situations where a code block must end with
                // whitespaces, tabs or new lines
                $code = rtrim($matches[3]);

                if ($language === '') {
                    $language = 'code';
                }

                $code = $self->highlightAndDecorateCode($code, $language, $parseEvent->app);

                return "\n\n" . $code;
            },
            $item['original']
        );
        $parseEvent->setItem($item);
    }

    /**
     * It parses the code blocks of the item content that use the
     * fenced style for code blocks:
     *   * the code listing starts with at least three ~~~
     *   * (optionally) followed by a whitespace + a dot + the programming language name
     *   * the lines of code don't include any leading tab or whitespace
     *   * the code listing ends with the same number of opening ~~~.
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
    private function parseFencedTypeCodeBlocks(ParseEvent $parseEvent): void
    {
        // variable needed for PHP 5.3
        $self = $this;

        $item = $parseEvent->getItem();
        // regexp adapted from PHP-Markdown
        $item['original'] = preg_replace_callback(
            '{
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
            function ($matches) use ($self, $parseEvent) {
                $language = $matches[2];

                // codeblocks always end with an empty new line (due to the regexp used)
                // the current solution rtrims() the whole block. This would not work
                // in the (very) rare situations where a code block must end with
                // whitespaces, tabs or new lines
                $code = rtrim($matches[3]);

                if ($language === '') {
                    $language = 'code';
                }

                $code = $self->highlightAndDecorateCode($code, $language, $parseEvent->app);

                return "\n\n" . $code;
            },
            $item['original']
        );
        $parseEvent->setItem($item);
    }
}
