<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Events\EasybookEvents;
use Easybook\Events\ParseEvent;
use Easybook\Templating\Renderer;
use Easybook\Util\CodeHighlighter;
use Iterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It parses and (optionally) highlights the syntax of the code listings.
 */
final class CodePluginEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var CodeHighlighter
     */
    private $codeHighlighter;

    /**
     * @var Renderer
     */
    private $renderer;

    public function __construct(CodeHighlighter $codeHighlighter, Renderer $renderer)
    {
        $this->codeHighlighter = $codeHighlighter;
        $this->renderer = $renderer;
    }

    public static function getSubscribedEvents(): Iterator
    {
        yield EasybookEvents::PRE_PARSE => ['parseCodeBlocks', -500];
        yield EasybookEvents::POST_PARSE => ['fixParsedCodeBlocks', -500];
    }

    /**
     * It parses different code blocks types (Markdown classic, fenced
     * and GitHub).
     *
     * @see 'Code block types' section in easybook-doc-en/05-publishing-html-books.md
     */
    public function parseCodeBlocks(ParseEvent $parseEvent): void
    {
        $this->parseGithubTypeCodeBlocks($parseEvent);
    }

    /**
     * It fixes the resulting contents of the parsed code blocks.
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
     *
     * @return string The resulting code after the highlight and rendering process
     */
    public function highlightAndDecorateCode(string $code, string $language): string
    {
        if ($application->edition('highlight_code')) {
            // highlight code if the edition wants to
            $code = $this->codeHighlighter->highlight($code, $language);
        } else {
            // escape code to show it instead of interpreting it

            // yaml-style comments could be interpreted as Markdown headings
            // replace any starting # character by its HTML entity (&#35;)
            $code = '<pre>'
                . preg_replace('/^# (.*)/', '&#35; $1', htmlspecialchars($code))
                . '</pre>';
        }

        return $this->renderer->render('code.twig', [
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
     *                          the $item being parsed
     */
    private function parseGithubTypeCodeBlocks(ParseEvent $parseEvent): void
    {
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
            function ($matches) use ($parseEvent) {
                $language = $matches[2];

                // codeblocks always end with an empty new line (due to the regexp used)
                // the current solution rtrims() the whole block. This would not work
                // in the (very) rare situations where a code block must end with
                // whitespaces, tabs or new lines
                $code = rtrim($matches[3]);

                if ($language === '') {
                    $language = 'code';
                }

                $code = $this->highlightAndDecorateCode($code, $language);

                return PHP_EOL . PHP_EOL . $code;
            },

            $item['original']
        );
        $parseEvent->setItem($item);
    }
}
