<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Events\EasybookEvents;
use Easybook\Events\ItemAwareEvent;
use Easybook\Publishers\Epub2Publisher;
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
    }

    /**
     * It parses different code blocks types.
     *
     * @see 'Code block types' section in easybook-doc-en/05-publishing-html-books.md
     */
    public function parseCodeBlocks(ItemAwareEvent $itemAwareEvent): void
    {
        // the syntax highlight is stricly not recommended for epub formats
        if ($itemAwareEvent->getEditionFormat() === Epub2Publisher::NAME) {
            return;
        }

        $this->parseGithubTypeCodeBlocks($itemAwareEvent);
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
    public function highlightAndDecorateCode(ItemAwareEvent $itemAwareEvent, string $code, string $language): string
    {
        if ($itemAwareEvent->getEditionFormat() !== Epub2Publisher::NAME) {
            // highlight code if the edition wants to
            $code = $this->codeHighlighter->highlight($code, $language);
        } else {
            // escape code to show it instead of interpreting it

            // yaml-style comments could be interpreted as Markdown headings
            // replace any starting # character by its HTML entity (&#35;)
            $code = '<pre>' . preg_replace('/^# (.*)/', '&#35; $1', htmlspecialchars($code)) . '</pre>';
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
     *                          the $item being parsed
     */
    private function parseGithubTypeCodeBlocks(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();

        // regexp adapted from PHP-Markdown
        $decoratedOriginal = preg_replace_callback(
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
            function ($matches) use ($itemAwareEvent) {
                $language = $matches[2];

                // codeblocks always end with an empty new line (due to the regexp used)
                // the current solution rtrims() the whole block. This would not work
                // in the (very) rare situations where a code block must end with
                // whitespaces, tabs or new lines
                $code = rtrim($matches[3]);

                if ($language === '') {
                    $language = 'code';
                }

                $code = $this->highlightAndDecorateCode($itemAwareEvent, $code, $language);

                return PHP_EOL . PHP_EOL . $code;
            },
            $item->getOriginal()
        );

        // are you use? not content
        $item->changeOriginal($decoratedOriginal);
    }
}
