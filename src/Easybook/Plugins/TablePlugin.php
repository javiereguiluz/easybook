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
 * It performs some operations on the book tables, such as
 * decorating their contents and adding labels to them.
 */
final class TablePlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Events::POST_PARSE => ['decorateAndLabelTables', -500],
        ];
    }

    /**
     * It decorates each table with a template and, if the edition configures it,
     * with the appropriate auto-numbered label.
     *
     * @param ParseEvent $event The object that contains the item being processed
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
