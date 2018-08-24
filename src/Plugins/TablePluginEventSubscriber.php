<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Events\EasybookEvents;
use Easybook\Events\ItemAwareEvent;
use Easybook\Templating\Renderer;
use Easybook\Util\Slugger;
use Iterator;
use Nette\Utils\Strings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It performs some operations on the book tables, such as decorating their contents and adding labels to them.
 */
final class TablePluginEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var Slugger
     */
    private $slugger;

    /**
     * @var string[]
     */
    private $listOfTables = [];

    /**
     * @var int
     */
    private $counter = 0;

    public function __construct(Renderer $renderer, Slugger $slugger)
    {
        $this->renderer = $renderer;
        $this->slugger = $slugger;
    }

    public static function getSubscribedEvents(): Iterator
    {
        yield EasybookEvents::POST_PARSE => ['decorateAndLabelTables', -500];
    }

    /**
     * It decorates each table with a template and, if the edition configures it,
     * with the appropriate auto-numbered label.
     */
    public function decorateAndLabelTables(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();

        $addTableLabels = in_array('table', $itemAwareEvent->app->edition('labels') ?: [], true);
        $parentItemNumber = $item['config']['number'];

        $this->listOfTables = [];
        $this->counter = 0;

        $item['content'] = Strings::replace(
            $item['content'],
            "#(?<content><table.*\n<\/table>)#Ums",
            function ($matches) use ($itemAwareEvent, $addTableLabels, $parentItemNumber) {
                // prepare table parameters for template and label
                $this->counter++;
                $parameters = [
                    'item' => [
                        'caption' => '',
                        'content' => $matches['content'],
                        'label' => '',
                        'number' => $this->counter,
                        'slug' => $this->slugger->slugify('Table ' . $parentItemNumber . '-' . $this->counter),
                    ],
                    'element' => [
                        'number' => $parentItemNumber,
                    ],
                ];

                // the publishing edition wants to label tables
                if ($addTableLabels) {
                    $label = $itemAwareEvent->app->getLabel('table', $parameters);
                    $parameters['item']['label'] = $label;
                }

                // add table details to the list-of-tables
                $this->listOfTables[] = $parameters;

                return $this->renderer->render('table.twig', $parameters);
            }
        );

        if (count($this->listOfTables) > 0) {
            //$itemAwareEvent->app->append('publishing.list.tables', $this->listOfTables);
        }

        $itemAwareEvent->setItem($item);
    }
}
