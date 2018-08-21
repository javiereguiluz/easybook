<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Events\EasybookEvents;
use Easybook\Events\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It performs some operations on the book tables, such as
 * decorating their contents and adding labels to them.
 */
final class TablePlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            EasybookEvents::POST_PARSE => ['decorateAndLabelTables', -500],
        ];
    }

    /**
     * It decorates each table with a template and, if the edition configures it,
     * with the appropriate auto-numbered label.
     */
    public function decorateAndLabelTables(ParseEvent $parseEvent): void
    {
        $item = $parseEvent->getItem();

        $addTableLabels = in_array('table', $parseEvent->app->edition('labels') ?: [], true);
        $parentItemNumber = $item['config']['number'];
        $listOfTables = [];
        $counter = 0;

        $item['content'] = preg_replace_callback(
            "/(?<content><table.*\n<\/table>)/Ums",
            function ($matches) use ($parseEvent, $addTableLabels, $parentItemNumber, &$listOfTables, &$counter) {
                // prepare table parameters for template and label
                $counter++;
                $parameters = [
                    'item' => [
                        'caption' => '',
                        'content' => $matches['content'],
                        'label' => '',
                        'number' => $counter,
                        'slug' => $parseEvent->app->slugify('Table ' . $parentItemNumber . '-' . $counter),
                    ],
                    'element' => [
                        'number' => $parentItemNumber,
                    ],
                ];

                // the publishing edition wants to label tables
                if ($addTableLabels) {
                    $label = $parseEvent->app->getLabel('table', $parameters);
                    $parameters['item']['label'] = $label;
                }

                // add table details to the list-of-tables
                $listOfTables[] = $parameters;

                return $parseEvent->app->render('table.twig', $parameters);
            },
            $item['content']
        );

        if (count($listOfTables) > 0) {
            $parseEvent->app->append('publishing.list.tables', $listOfTables);
        }

        $parseEvent->setItem($item);
    }
}
