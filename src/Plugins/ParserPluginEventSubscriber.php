<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Events\EasybookEvents;
use Easybook\Events\ItemAwareEvent;
use Easybook\Templating\Renderer;
use Iterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ParserPluginEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var mixed[]
     */
    private $labels = [];

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @param mixed[] $labels
     */
    public function __construct(array $labels, Renderer $renderer)
    {
        $this->labels = $labels;
        $this->renderer = $renderer;
    }

    public static function getSubscribedEvents(): Iterator
    {
        yield EasybookEvents::PRE_PARSE => [['normalizeMarkdownHeaders', -1000]];

        yield EasybookEvents::POST_PARSE => [
            ['fixHtmlCode', -1000],
            ['setItemTitle', -1000],
            ['addSectionLabels', -1000],
        ];
    }

    /**
     * It modifies the original Markdown content to replace the SetExt-style
     * headers by ATX-style headers. This is necessary to avoid problems with
     * auto-numbering of sections when mixing both styles in a single book.
     */
    public function normalizeMarkdownHeaders(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();

        $item->changeOriginal(preg_replace_callback(
            '{
                (^.+?)                              # $1: Header text
                (?:[ ]+\{\#([-_:a-zA-Z0-9]+)\})?    # $2: Id attribute
                [ ]*\n(=+|-+)[ ]*\n+                # $3: Header footer
            }Umx',
            function ($matches) {
                $level = $matches[3]{0} === '=' ? 1 : 2;

                return sprintf('%s %s%s', str_repeat('#', $level), $matches[1], $matches[2]);
            },
            $item->getOriginal()
        ));
    }

    /**
     * It fixes the resulting HTML code of the book item. This is necessary
     * to avoid problems with the invalid-HTML-markup-sensitive editions such as the ePub books.
     */
    public function fixHtmlCode(ItemAwareEvent $itemAwareEvent): void
    {
        // replace <br> by <br/> (it causes problems for epub books)
        $item = $itemAwareEvent->getItem();
        $item->changeContent(str_replace('<br>', '<br/>', $item->getContent()));
    }

    /**
     * Sets the book item title by extracting it from its contents or
     * by using the default title for that book item type.
     */
    public function setItemTitle(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();

        if (count($item->getTableOfContents()) > 0) {
            $firstItemSection = $item->getTableOfContents()[0];

            // the title of the content can only be a <h1> heading
            if ($firstItemSection['level'] === 1) {
                $item->setSlug($firstItemSection['slug']);
                $item->setTitle($firstItemSection['title']);

                // strip the title from the parsed content, because the book templates
                // always display the title separately from the rest of the content
                $item->changeContent(preg_replace('/^<h1.*<\/h1>\n+(.*)/x', '$1', $item->getContent()));
            }
        }

        // ensure that every item has a title by using the default title if necessary
        if ($item->getTitle()) {
            $item->setTitle($this->renderer->render($item->getTitle(), [$item->getConfigElement()]));
        }
    }

    /**
     * It adds the appropriate auto-numbered labels to the book item sections.
     */
    public function addSectionLabels(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();

        // special book items without a TOC don't need labels
        if (count($item->getTableOfContents()) === 0) {
            return;
        }

        $counters = [
            1 => $item->getConfigNumber(),
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
        ];
        $addSectionLabels = $labels[$item->getConfigElement()] ?? [];

        foreach ($item->getTableOfContents() as $key => $entry) {
            if ($addSectionLabels) {
                $level = $entry['level'];

                if ($level > 1) {
                    $counters[$level]++;
                }

                // reset the counters for the higher heading levels
                for ($i = $level + 1; $i <= 6; $i++) {
                    $counters[$i] = 0;
                }

                $parameters = [
                    'counters' => $counters,
                    'level' => $level,
                ];
                $parameters['title'] = $item->getItemConfig()->getTitle();
                $parameters['content'] = $item->getItemConfig()->getContent();
                $parameters['number'] = $item->getItemConfig()->getNumber();
                $parameters['element'] = $item->getItemConfig()->getElement();

                $label = $this->renderer->render($this->labels[$item->getConfigElement()], [
                    'item' => $parameters,
                ]);
            } else {
                $label = '';
            }

            $entry['label'] = $label;
            $item->addTableOfContentItem($key, $entry);
        }

        // the label of the item matches the label of its first TOC element
        $item->changeLabel($item->getTableOfContents()[0]['label']);

        // add section labels to the content
        foreach ($item->getTableOfContents() as $entry) {
            // the parsed title can be different from the TOC entry title
            // that's the case for the titles with markup code inside (* ` ** etc.)
            // thus, the replacement must be done based on a fuzzy title that
            // doesn't include the title text
            $fuzzyTitle = '/<h' . $entry['level'] . ' id="' . $entry['slug'] . "\">.*<\/h" . $entry['level'] . ">\n\n/";

            $labeledTitle = sprintf(
                '<h%s id="%s">%s%s</h%s>' . PHP_EOL . PHP_EOL,
                $entry['level'],
                $entry['slug'],
                $entry['label'],
                $entry['label'] !== '' ? ' ' . $entry['title'] : $entry['title'],
                $entry['level']
            );

            $item->changeContent(preg_replace($fuzzyTitle, $labeledTitle, $item->getContent()));
        }
    }
}
