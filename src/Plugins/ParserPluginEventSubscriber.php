<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Events\EasybookEvents;
use Easybook\Events\ParseEvent;
use Easybook\Util\Slugger;
use Iterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It performs some operations on the book items after they have been parsed.
 */
final class ParserPluginEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Slugger
     */
    private $slugger;

    public function __construct(Slugger $slugger)
    {
        $this->slugger = $slugger;
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
    public function normalizeMarkdownHeaders(ParseEvent $parseEvent): void
    {
        $item = $parseEvent->getItem();

        $item['original'] = preg_replace_callback(
            '{
                (^.+?)                              # $1: Header text
                (?:[ ]+\{\#([-_:a-zA-Z0-9]+)\})?    # $2: Id attribute
                [ ]*\n(=+|-+)[ ]*\n+                # $3: Header footer
            }Umx',
            function ($matches) {
                $level = $matches[3]{0} === '=' ? 1 : 2;

                return sprintf('%s %s%s', str_repeat('#', $level), $matches[1], $matches[2]);
            },
            $item['original']
        );

        $parseEvent->setItem($item);
    }

    /**
     * It fixes the resulting HTML code of the book item. This is necessary
     * to avoid problems with the invalid-HTML-markup-sensitive editions such
     * as the ePub books.
     */
    public function fixHtmlCode(ParseEvent $parseEvent): void
    {
        // replace <br> by <br/> (it causes problems for epub books)
        $item = $parseEvent->getItem();
        $item['content'] = str_replace('<br>', '<br/>', $item['content']);
        $parseEvent->setItem($item);
    }

    /**
     * Sets the book item title by extracting it from its contents or
     * by using the default title for that book item type.
     */
    public function setItemTitle(ParseEvent $parseEvent): void
    {
        $item = $parseEvent->getItem();

        if (count($item['toc']) > 0) {
            $firstItemSection = $item['toc'][0];

            // the title of the content can only be a <h1> heading
            if ($firstItemSection['level'] === 1) {
                $item['slug'] = $firstItemSection['slug'];
                $item['title'] = $firstItemSection['title'];

                // strip the title from the parsed content, because the book templates
                // always display the title separately from the rest of the content
                $item['content'] = preg_replace('/^<h1.*<\/h1>\n+(.*)/x', '$1', $item['content']);
            }
        }

        // ensure that every item has a title by using
        // the default title if necessary
        if ($item['title'] === '') {
            $item['title'] = $parseEvent->app->getTitle($item['config']['element']);
            $item['slug'] = $this->slugger->slugify($item['title']);
        }

        $parseEvent->setItem($item);
    }

    /**
     * It adds the appropriate auto-numbered labels to the book item sections.
     */
    public function addSectionLabels(ParseEvent $parseEvent): void
    {
        $item = $parseEvent->getItem();

        // special book items without a TOC don't need labels
        if (count($item['toc']) === 0) {
            return;
        }

        $counters = [
            1 => $item['config']['number'],
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
        ];
        $addSectionLabels = in_array($item['config']['element'], $parseEvent->app->edition('labels') ?: [], true);

        foreach ($item['toc'] as $key => $entry) {
            if ($addSectionLabels) {
                $level = $entry['level'];

                if ($level > 1) {
                    $counters[$level]++;
                }

                // reset the counters for the higher heading levels
                for ($i = $level + 1; $i <= 6; $i++) {
                    $counters[$i] = 0;
                }

                $parameters = array_merge($item['config'], [
                    'counters' => $counters,
                    'level' => $level,
                ]);

                $label = $parseEvent->app->getLabel($item['config']['element'], [
                    'item' => $parameters,
                ]);
            } else {
                $label = '';
            }

            $entry['label'] = $label;
            $item['toc'][$key] = $entry;
        }

        // the label of the item matches the label of its first TOC element
        $item['label'] = $item['toc'][0]['label'];

        // add section labels to the content
        foreach ($item['toc'] as $i => $entry) {
            // the parsed title can be different from the TOC entry title
            // that's the case for the titles with markup code inside (* ` ** etc.)
            // thus, the replacement must be done based on a fuzzy title that
            // doesn't include the title text
            $fuzzyTitle = '/<h' . $entry['level'] . ' id="' . $entry['slug'] . "\">.*<\/h" . $entry['level'] . ">\n\n/";

            $labeledTitle = sprintf(
                "<h%s id=\"%s\">%s%s</h%s>\n\n",
                $entry['level'],
                $entry['slug'],
                $entry['label'],
                ($entry['label'] !== '') ? ' ' . $entry['title'] : $entry['title'],
                $entry['level']
            );

            $item['content'] = preg_replace($fuzzyTitle, $labeledTitle, $item['content']);
        }

        $parseEvent->setItem($item);
    }
}
